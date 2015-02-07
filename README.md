PROOPH PROCESSING
=================

Workflow processing made easy - powered by prooph software GmbH

[![Build Status](https://travis-ci.org/prooph/processing.svg?branch=master)](https://travis-ci.org/prooph/processing)

# tl;dr

Prooph processing offers various components to set up and maintain automated data processing. It is a glue component
sitting between applications to control the exchange of data (including import/export).

# The Idea

Shifting data between applications belongs to our daily developer business. We write job scripts, build APIs, consume APIs, import files by hand, and many more. Sometimes a few lines of code are enough to get the job done but a data workflow can become quite complex and require lots of development time. Prooph processing wants to simplify the process by offering a ready to use communication, translation, processing and logging layer based on PHP and a carefully selected list of open source libraries.

# A Framework?

Right! The core of the processing ecosystem is designed as a framework. This allows to pick the components you really need to achieve your goals and keeps the system flexible. You will find more packages on our organisation github page [prooph](https://github.com/prooph). We offer an easy to use [UI](https://github.com/prooph/link) to configure, manage and monitor your data workflow and we will also offer plugins and connectors for various open source systems. So stay tuned!

# Can we use it in production?

Yes and no. The software is in an early state. That doesn't mean it's not working but most of the documentation is missing. However, if you like what you've read and you want to be an early adopter don't hesitate to contact us at contact[at]prooph.de. We offer consulting and support for the system.

# Examples

Please see [examples doc](examples/README.md) to get started with prooph processing.

# Components

- [Prooph\Processing\Type](library/Type/README.md)
- [Prooph\Processing\Message](library/Message/README.md)
- [Prooph\Processing\Processor](library/Processor/README.md)
- [Prooph\Processing\Console](library/Console/README.md)
- [Prooph\Processing\Environment](library/Environment/README.md)

