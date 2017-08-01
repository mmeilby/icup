iCUP API Documentation 
======================

Tournament system for Interamnia World Cup

Host
----
entity = Host
name
alias
domain

Tournament
----------
entity = Tournament
host
key
name
edition
description

Category
--------
entity = Category
tournament
key
name
gender
classification
age

Group
-----
entity = Group
category
key
name
classification

Site
----
entity = Site
name
venues

Venue
-----
entity = Venue
key
no
name
location
site

Match
-----
entity = Match
key
matchno
matchtype
date
time
category
group
venue
home
away

Club
----
entity = Club
key
name
address
city
country_code
flag

Team
----
entity = Team
key
name
teamname
club
color
division
vacant
country_code
flag

News
----
entity = News
date
title
context
language
no
type
team
match

Timeslot
--------
entity = Timeslot
name

Enrollment
----------
entity = Enrollment
date
category
team



Tournament
----------
/service/api/v1/tournament
entity = [empty], Tournament

Category
--------
/service/api/v1/category
entity = Tournament, Category

Group
-----
/service/api/v1/group
entity = Tournament, Category, Group

Site
----
/service/api/v1/site
entity = Tournament

Venue
-----
/service/api/v1/venue
entity = Tournament, Venue

Match
-----
/service/api/v1/match
entity = Tournament, Group, Venue, Match

Club
----
/service/api/v1/club
entity = Tournament, Club

News
----
/service/api/v1/news
entity = Tournament

Timeslot
--------
/service/api/v1/timeslot
entity = Tournament

Enrollment
----------
/service/api/v1/enrollment
entity = Tournament
