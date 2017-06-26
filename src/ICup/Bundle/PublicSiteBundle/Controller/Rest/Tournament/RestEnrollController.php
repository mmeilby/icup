<?php

namespace ICup\Bundle\PublicSiteBundle\Controller\Rest\Tournament;

use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Category;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Club;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\ClubRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Date;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\EnrollmentDetail;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\SocialRelation;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Team;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Tournament;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\User;
use ICup\Bundle\PublicSiteBundle\Entity\Doctrine\Voucher;
use ICup\Bundle\PublicSiteBundle\Entity\EnrollmentTeamCheckoutForm;
use ICup\Bundle\PublicSiteBundle\Exceptions\ValidationException;
use ICup\Bundle\PublicSiteBundle\Form\EnrollTeamCheckoutType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use Twig_Environment;
use Swift_Message;
use DateTime;
use Exception;

/**
 * Doctrine\Enroll controller.
 *
 * @Route("/rest/enroll")
 */
class RestEnrollController extends Controller
{
    /**
     * Creates a new Doctrine\Enroll entity.
     * @Route("/{tournamentid}", name="rest_enroll_team_checkout", options={"expose"=true})
     * @Method("POST")
     * @param Request $request
     * @param tournamentid
     * @return JsonResponse
     */
    public function newAction(Request $request, $tournamentid)
    {
        /* @var $twig Twig_Environment */
        $twig = $this->get('twig');
        $twig->getExtension('Twig_Extension_Core')
            ->setNumberFormat(0,
                $this->get('translator')->trans('FORMAT.DECIMALPOINT', array(), 'messages'),
                $this->get('translator')->trans('FORMAT.THOUSANDSEPERATOR', array(), 'messages'));
        $enrollmentDetail = new EnrollmentDetail();
        /* @var $tournament Tournament */
        try {
            $tournament = $this->get('entity')->getTournamentById($tournamentid);
            $enrollmentDetail->setTournament($tournament);
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_NOT_FOUND);
        }

        $enrolledForm = new EnrollmentTeamCheckoutForm();
        $form = $this->createForm(new EnrollTeamCheckoutType(), $enrolledForm);
        $form->handleRequest($request);

        try {
            if ($this->checkForm($form, $tournament, $enrolledForm)) {
                $cardVerified = $enrolledForm->getToken() != null;
                $em = $this->getDoctrine()->getManager();
                $em->beginTransaction();
                try {
                    $clubref = $enrolledForm->getClub();
                    /* @var $club Club */
                    $club = $this->get('logic')->getClubByName($clubref['name'], $clubref['country']);
                    if ($club == null) {
                        // New club enrolled - user is allowed to take control
                        $newClubEnrolled = true;
                        $club = new Club();
                        $club->setName($clubref['name']);
                        $club->setCountry($this->get('entity')->getCountryRepo()->find($clubref['country']));
                        $em->persist($club);
                        $em->flush();
                    }
                    else {
                        // Existing club enrolled - user must be administrator to place enrollment
                        $newClubEnrolled = false;
                    }
                    $userref = $enrolledForm->getManager();
                    /* @var $user User */
                    $user = $this->get('logic')->getUserByEmail($userref['email']);
                    if ($user == null) {
                        if (!$newClubEnrolled) {
                            // New users are not allowed to enroll existing clubs
                            throw new ValidationException("NOTALLOWEDTOENROLL");
                        }
                        $newUserCreated = true;
                        $user = new User();
                        $user->setUsername($userref['email']);
                        $user->setName($userref['name']);
                        $user->setEmail($userref['email']);
                        $user->addRole(User::ROLE_CLUB_ADMIN);
                        $secret = $this->get('util')->generatePassword($user);
                        $user->setEnabled(true);
                        $em->persist($user);
                        $em->flush();
                    }
                    else {
                        $newUserCreated = false;
                        // Existing users must be admin to enroll an existing club
                        if (!$newClubEnrolled && !$user->isOfficialOf($club)) {
                            // Users that are neither officials nor managers are not allowed to enroll existing clubs
                            throw new ValidationException("NOTALLOWEDTOENROLL");
                        }
                        $secret = '';
                    }
                    if ($newClubEnrolled) {
                        // Setup initial manager role for this user
                        $relation = new ClubRelation();
                        $relation->setClub($club);
                        $relation->setUser($user);
                        $relation->setStatus(ClubRelation::$MEM);
                        $relation->setRole(ClubRelation::$MANAGER);
                        $relation->setApplicationDate(Date::getDate(new DateTime()));
                        $relation->setMemberSince(Date::getDate(new DateTime()));
                        $relation->setLastChange(Date::getDate(new DateTime()));
                        $em->persist($relation);
                        $em->flush();
                        $user->getClubRelations()->add($relation);
                        $club->getOfficials()->add($relation);
                    }
                    $enrollmentDetail->setClub($club);
                    $enrollmentDetail->setName($userref['name']);
                    $enrollmentDetail->setEmail($userref['email']);
                    $enrollmentDetail->setMobile($userref['mobile']);
                    $enrollmentDetail->setState(EnrollmentDetail::STATUS_ENROLLED);
                    $em->persist($enrollmentDetail);
                    $em->flush();

                    $total = 0;
                    $noofteams = 0;
                    $currency = '';
                    $enrolledCategories = array();
                    foreach ($enrolledForm->getEnrolled() as $team) {
                        $category = $tournament->getCategories()->filter(function (Category $category) use ($team) { return $category->getId() == $team['id']; })->first();
                        $quantity = $team['quantity'];
                        $enrollmentPrice = $this->get('logic')->getEnrollmentPrice($category, DateTime::createFromFormat("Y-m-d", $enrolledForm->getTxTimestamp()));
                        $total += ($enrollmentPrice['fee'] + $enrollmentPrice['deposit'])*$quantity;
                        $noofteams += $quantity;
                        $currency = $enrollmentPrice['currency'];
                        $enrolledCategories[] = array(
                            'category' => $category,
                            'category_description' =>
                                $this->get('translator')->trans('CATEGORY', array(), 'tournament') .
                                " " . $category->getName() . " - " .
                                $this->get('translator')->transChoice(
                                    'GENDER.'.$category->getGender().$category->getClassification(),
                                    $category->getAge(),
                                    array('%age%' => $category->getAge()), 'tournament'),
                            'quantity' => $quantity,
                            'fee' => $enrollmentPrice['fee']*$quantity,
                            'deposit' => $enrollmentPrice['deposit']*$quantity,
                            'currency' => $enrollmentPrice['currency']
                        );
                        while ($quantity > 0) {
                            $this->get('logic')->addEnrolled($category, $club, $user);
                            $quantity--;
                        }
                    }

                    $voucher = new Voucher();
                    $voucher->setClub($club);
                    $voucher->setDate(Date::getDate(new DateTime()));
                    $voucher->setAmount($total*100);
                    $voucher->setCurrency($currency);
                    $voucher->setVoucherid($enrollmentDetail->getId());
                    $voucher->setVtype(Voucher::VOUCHER_TYPE_ENROLLMENT);
                    $em->persist($voucher);
                    $em->flush();

                    $club->getVouchers()->add($voucher);
                    $em->commit();
                }
                catch (Exception $e) {
                    $em->rollBack();
                    return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $orderData = array(
                    'form' => $enrolledForm,
                    'tournament' => $tournament,
                    'club' => $club,
                    'user' => array(
                        'newuser' => $newUserCreated,
                        'username' => $user->getUsername(),
                        'secret' => $secret
                    ),
                    'checkout' => array(
                        'categories' => $enrolledCategories,
                        'charge' => $total,
                        'teams' => $noofteams,
                        'currency' => $currency,
                        'bank' => array(
                            'name' => 'BANCA POPOLARE DI BARI SCPA',
                            'account' => 'CC0900053052',
                            'iban' => 'IT22 X 05424 15300 000000053052',
                            'swift' => 'BPBAIT 3BXXX',
                            'advis' => 'INTERAMNIA WORLD CUP'
                        ),
                        'merchant' => 'INTERAMNIA WORLD CUP',
                        'prepaid' => $cardVerified
                    )
                );

                // send confirmation mail
                $this->sendConfirmation($userref['name'], $userref['email'], $orderData);

                if ($cardVerified) {
                    // Set your secret key: remember to change this to your live secret key in production
                    // See your keys here: https://dashboard.stripe.com/account/apikeys
                    Stripe::setApiKey($this->container->getParameter("StripeSecret"));

                    $customer = Customer::create(array(
                        "email" => $userref['email'],
                        "source" => $enrolledForm->getToken(),
                    ));

                    $charge = Charge::create(array(
                        "amount" => $total*100,
                        "currency" => $currency,
                        "customer" => $customer->id,
                        "statement_descriptor" => "ICUP PRE ENROLLMENT"
                    ));

                    $orderData['stripe'] = $charge;

                    // check $charge->status
                    if ($charge['status'] !== "succeeded") {
                        return new JsonResponse(array('errors' => array($charge['status'])), Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    // send transaction mail
                    $this->sendTransaction($userref['name'], $userref['email'], $orderData);

                    $em->beginTransaction();
                    try {
                        $voucher = new Voucher();
                        $voucher->setClub($club);
                        $voucher->setDate(date(Date::$db_date_format, $charge['created']));
                        $voucher->setAmount($charge['amount']);
                        $voucher->setCurrency($charge['currency']);
                        $voucher->setVoucherid($charge['balance_transaction']);
                        $voucher->setVtype(Voucher::VOUCHER_TYPE_CARD_PAYMENT);
                        $em->persist($voucher);
                        $em->flush();

                        $club->getVouchers()->add($voucher);

                        $enrollmentDetail->setState(EnrollmentDetail::STATUS_CARD_PAID);
                        $em->commit();
                    }
                    catch (Exception $e) {
                        $em->rollBack();
                        $logger = $this->get('logger');
                        $logger->error($e->getMessage());
                        $logger->error("Received payment for enrollment of ".$club, $charge);
                        return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }

                // send customer info to admins
                $this->sendAdminMsg($orderData);

                return new JsonResponse(array(), Response::HTTP_NO_CONTENT);
            }
        }
        catch (ValidationException $e) {
            return new JsonResponse(array('errors' => array($e->getMessage())), Response::HTTP_BAD_REQUEST);
        }

        $errors = array();
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(array('errors' => $errors), Response::HTTP_BAD_REQUEST);
    }

    private function sendTransaction($user, $email, $orderData) {
        $from = array($this->container->getParameter('mailer_user') => "icup.dk support");
        $recv = array($email => $user);
        $mailbody = $this->renderView('ICupPublicSiteBundle:Email:enrolledtxmail.html.twig', $orderData);
        $message = Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('FORM.ENROLLMENT.MAIL.CARDTX.TITLE', array(), 'club'))
            ->setFrom($from)
            ->setTo($recv)
            ->setBody($mailbody, 'text/html');
        $this->get('mailer')->send($message);
    }

    private function sendConfirmation($user, $email, $orderData) {
        $from = array($this->container->getParameter('mailer_user') => "icup.dk support");
        $recv = array($email => $user);
        $mailbody = $this->renderView('ICupPublicSiteBundle:Email:enrolledconfirmmail.html.twig', $orderData);
        $message = Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('FORM.ENROLLMENT.MAIL.TITLE', array(), 'club'))
            ->setFrom($from)
            ->setTo($recv)
            ->setBody($mailbody, 'text/html');
        $this->get('mailer')->send($message);
    }

    private function sendAdminMsg($orderData) {
        $from = array($this->container->getParameter('mailer_user') => "icup.dk support");
        $admins = $this->get('logic')->listAdminUsers();
        if (count($admins) < 1) {
            $recv = $from;
        }
        else {
            $recv = array();
            /* @var $admin User */
            foreach ($admins as $admin) {
                if ($admin->getEmail() != '' && $admin->getName() != '') {
                    $recv[$admin->getEmail()] = $admin->getName();
                }
            }
        }
        $mailbody = $this->renderView('ICupPublicSiteBundle:Email:enrolledmail.html.twig', $orderData);
        $message = Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('FORM.ENROLLMENT.MAIL.ADMIN.TITLE', array(), 'club'))
            ->setFrom($from)
            ->setTo($recv)
            ->setBody($mailbody, 'text/html');
        $this->get('mailer')->send($message);
    }

    private function checkForm(Form $form, Tournament $tournament, EnrollmentTeamCheckoutForm $enrolledForm) {
        if ($form->isValid()) {
            if ($enrolledForm->getTxTimestamp() == null || trim($enrolledForm->getTxTimestamp()) == '') {
                $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
            }
            else {
                $timestamp = DateTime::createFromFormat("Y-m-d", $enrolledForm->getTxTimestamp());
                if ($timestamp === false) {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
                }
            }
            if ($enrolledForm->getClub() == null || !is_array($enrolledForm->getClub())) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
            }
            else {
                $club = $enrolledForm->getClub();
                if (!isset($club['name']) || trim($club['name']) == '') {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
                }
                if (!isset($club['country']) || trim($club['country']) == '') {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
                }
            }
            if ($enrolledForm->getManager() == null || !is_array($enrolledForm->getManager())) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
            }
            else {
                $manager = $enrolledForm->getManager();
                if (!isset($manager['name']) || trim($manager['name']) == '') {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
                }
                if (!isset($manager['email']) || trim($manager['email']) == '') {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
                }
                if (!isset($manager['mobile']) || trim($manager['mobile']) == '') {
                    $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
                }
            }
            if ($enrolledForm->getEnrolled() == null || !is_array($enrolledForm->getEnrolled())) {
                $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
            }
            else {
                foreach ($enrolledForm->getEnrolled() as $team) {
                    if (!isset($team['id']) || trim($team['id']) == '') {
                        $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
                    }
                    else {
                        if (!$tournament->getCategories()->exists(function ($key, Category $category) use ($team) { return $category->getId() == $team['id']; })) {
                            $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
                        }
                    }
                    if (!isset($team['quantity']) || trim($team['quantity']) == '') {
                        $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
                    }
                    else if (!is_int($team['quantity']) || $team['quantity'] < 1 || $team['quantity'] > 99) {
                        $form->addError(new FormError($this->get('translator')->trans('FORM.ENROLL.NONAME', array(), 'admin')));
                    }
                }
            }
        }
        return $form->isValid();
    }
}
