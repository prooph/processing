gingerframework
===============

Ginger Workflow Framework

[![Build Status](https://travis-ci.org/gingerframework/gingerframework.svg?branch=master)](https://travis-ci.org/gingerframework/gingerframework)

# What can I do with Ginger?

Ginger Workflow Framework offers various components to set up and maintain automated data flows. It is a glue component
sitting between systems which need to exchange data (including import/export scenarios).

# Why should I use Ginger?

Shifting data between systems belongs to our daily developer business. We write job scripts, build APIs, consume APIs, import
files by hand, and many more. Sometimes a few lines of code are enough to get the job done but a data workflow can become quite
complex and require lots of development time. In most cases the result of such efforts are hard connections between two systems.
When we receive the request to include a third or fourth system in the communication we have to start from scratch or at least
refactor the current solution to deal with the new requirements. Ginger Workflow Framework wants to simplify the process by
offering a ready to use communication, translation, processing and logging layer. Once you have connected a system with a Ginger WorkflowProcessor
you can connect any other system to it by simply connecting the new system with the Ginger WorkflowProcessor, too.
An included logging system based on a pattern called event sourcing tracks your data flow so you'll always know what happened.
The communication layer works with a service-oriented architecture to decouple systems from each other.
This allows long-running processes distributed over different systems.

# Why is it called Framework?

The core of the ginger ecosystem is designed as a framework. This allows to pick the components you really need to achieve your goals
and keeps the system flexible. In the future you will find more packages under the vendor "gingerframework". We will offer
an easy to use UI to configure, manage and monitor your data flows and we will also offer plugins and connectors for various
open source systems. So stay tuned!

# Can I use it in production?

No and yes. As you can see under the releases tab we don't reached a stable version yet and therefor currently don't support
semantic versioning. But if you want to use it in production you can send us an email at contact[at]prooph.de and we'll find a way.


# Components

- [Ginger\Type](library/Ginger/Type/README.md)
- [Ginger\Message](library/Ginger/Message/README.md)
- [Ginger\Processor](library/Ginger/Processor/README.md)
- [Ginger\Console](library/Ginger/Console/README.md)
- [Ginger\Environment](library/Ginger/Environment/README.md)

