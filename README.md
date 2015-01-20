Ginger Workflow Framework
=========================

Process Automation Made Easy - powered by prooph software

[![Build Status](https://travis-ci.org/gingerwork/gingerframework.svg?branch=master)](https://travis-ci.org/gingerwork/gingerframework)

# tl;dr

Ginger Workflow Framework offers various components to set up and maintain automated data flows. It is a glue component
sitting between systems to control the exchange of data (including import/export).

# The Idea

Shifting data between systems belongs to our daily developer business. We write job scripts, build APIs, consume APIs, import files by hand, and many more. Sometimes a few lines of code are enough to get the job done but a data workflow can become quite complex and require lots of development time. Ginger Workflow Framework wants to simplify the process by offering a ready to use communication, translation, processing and logging layer based on PHP and a carefully selected list of open source libraries. 

# A Framework?

Right! The core of the Ginger IWM ecosystem is designed as a framework. This allows to pick the components you really need to achieve your goals and keeps the system flexible. You will find more packages under the vendor [gingerwork](https://github.com/gingerwork). We offer an easy to use [UI](https://github.com/gingerwork/app-core) to configure, manage and monitor your data flows and we will also offer plugins and connectors for various open source systems. So stay tuned!

# Can we use it in production?

Yes and no. The software is in an early state. That doesn't mean it's not working but most of the documentation is missing. However, if you like what you've read and you want to be an early adopter don't hesitate to contact us at contact[at]prooph.de. We offer consulting and support for the system.

# Examples

Please see [examples doc](examples/README.md) to get started with Ginger.

# Components

- [Ginger\Type](library/Ginger/Type/README.md)
- [Ginger\Message](library/Ginger/Message/README.md)
- [Ginger\Processor](library/Ginger/Processor/README.md)
- [Ginger\Console](library/Ginger/Console/README.md)
- [Ginger\Environment](library/Ginger/Environment/README.md)

