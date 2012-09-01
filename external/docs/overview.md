## Welcome to inKWell

Compared to the previous version of inKWell (1.0), inKWell 2.0 represents a major shift in how the
framework operates.  It is **way** more object oriented and provides modern facilities to make such
development a joy.

## Influences

The inKWell framework has been in development for nearly 3 years.  It has predominately been
influenced by the modernization of PHP and the PHP Community, however, also derives many of its
concepts from frameworks in other languages (Play) and Unix operating systems.

## Goals

1) Environment Agnosticism
2) Drop-In Modules
3) Strong Separation of Concerns
4) Robust Features

### Environment Agnosticism

There are a handful of frameworks which claim to be RESTful.  There are also a handful of
frameworks that claim to have RESTful pieces (usually controllers).  While inKWell prides itself
on facilities that enable RESTful architecture (far more robust than other frameworks), it does
not tie itself to a particular architecture or for that matter protocol.

Not only is inKWell portable in the sense that it should run equally well on Windows, Linux, OS X,
BSD systems, etc... but it is portable in the sense that it should be as equally functional for a
console based application as it should be a web based application.

### Drop-In Modules

The first version of inKWell was the first and perhaps only framework which had the necessary
facilities to allow for entire applications, application components, etc, to be dropped into
place with no additional configuration or setup requirements.  Unzip the file and it's installed.

This is achieved through inKWell's modular configuration system which allowed, for example,
controller configurations to define and extend routes.  This emphasize also warranted the creation
of a few `git` based scripts to help developers create such packages.  We have continued this
tradition with inKWell 2.0

### Strong Separation of Concerns

At the core, inKWell is an MVC framework.  Although it places higher emphasis on HMVC than
traditional web MVC, it follows the key separation of concern principles that all MVC employs.
In addition, however, inKWell takes things one step further by promoting the separation of
data from logic in its configuration system.  Reconfiguring inKWell requires **zero** knowledge
of it's API.

### Robust Features (AKA: Speed)

Along with its associated libraries, inKWell intends to contend with the most full featured
frameworks available... as well as the fastest.  While these two goals are often at odds with
one another, it is our philosophy that robust and flexible solutions are born out of simple
code and coding principles, which in turn impart speed.
