iCUP API Documentation 
======================

Tournament system for Interamnia World Cup


Overview
========

Introduction
------------

The iCUP API allows you to programmatically access and modify your data in iCUP tournaments. It’s a collection of methods that you can invoke using straightforward HTTP POST requests. Results are returned in JSON format.

API keys
--------

You authenticate to the API by providing an API key. You can create and manage your API keys in the settings in iCUP administration pages. You must be administrator in iCUP to do this.

You can have multiple keys and use them for different purposes. You can optionally give each key a name to remember what it is used for.

Note: An API key gives full access to all data in your host account, so make sure to keep them secret. To revoke a key, delete it.

Requests
--------

To invoke a method in the API, make an HTTP POST request to this URL:

https://icup.dk/service/api/v1/<method name>

You can see the available methods below.

Here is an example that gets a tournament, by invoking the tournament method using the command-line tool curl:

curl https://icup.dk/service/api/v1/tournament -k
  -u bob@example.com:69C0A6A9E957B6398BD8C62F3B67C95005CA...
  -d entity=Tournament
  -d key=729069282AFE

You must always use HTTPS. Attempts to use HTTP will receive a redirection response.

All requests must authenticate using HTTP Basic authentication. As username, provide the email address of a user in your account that the request is made on behalf of. As password, provide an API key.

The user that you specify in the authentication determines the permissions that you have in the request. Also, if your request modifies any data, that user will appear in iCUP as the person that made the modification.

Parameters to methods must be passed as POST parameters, in URL-encoded query string format. This is the standard format used by an HTML form that uses POST (in other words application/x-www-form-urlencoded). Remember to URL encode each parameter value.

A parameter that is optional can be left out. If left out, the parameter will default to the value that means none, typically null or -1.

To explicitly specify null as a parameter value, use the string "null" (without the quotes). This string is always treated as meaning null. But you can typically leave out the parameter instead.

All names of methods, objects, properties, enumerations, etc. are case-sensitive in the API.

Results
-------

If a method invocation succeeds, you will receive the HTTP response code 200 OK, and a Result object in JSON format.

Here’s an example result from the tournament method:

{
  "entity" : "Tournament",
  "host" : { "entity" : "Host", "name" : "Host name" },
  "key" : "729069282AFE",
  "name" : "Tournament name",
  "edition" : "First edition",
  "description" : "Description"
}

DATA STRUCTURES
===============

Host
----
entity = Host
name

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

METHODS
=======

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
