## Definitions

This document is designed to familiarize you with terms that are common to inKWell.  Depending on
how in depth your development with inKWell will get, you may or may not need to know these.  For
most users creating a simple app, you will not.  For users who are looking to extend inKWell you
will.

### Extension

An extension in inKWell is a zip file containing the requisite files to extend the functionality
of inKWell itself.  An example of such might be an "ActiveRecordController" which provides a
base controller class and/or necessary scaffolding to build controllers which provide CRUD
operations to available Active Records.  Other, simpler, examples might include any of the
following:

- ResponseJSON : Adds handling of JSON to the Response class
- ResponseXML : Adds handling of XML to the Response class
- ARPasswordColumns : Adds easily configured password column functionality to Active Records

### Module

A module, unlike an extension does no provide or extend inKWell itself, but will add a specific
feature to your site or application.  An example of this might be a "forums" module which will add
forums to your site at the url '/forums/'.  Modules may depend on various extensions to operate.

### Library

A library