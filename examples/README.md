Workflow Processing Examples
============================

Prooph processing ships with some examples showing you simplified workflow scenarios.

# Example 1 - Write collected data to a file

To get started you can have a look at [example1-script.php](example1-script.php).
The annotated source code introduces the main components of the processing framework.
Of course you can also run the example. Simply open a console and navigate to the examples dir of the package then
fire up `php example1-script.php`. Please make sure that the script has read and write access
to the `data` folder otherwise you will get error messages.
The script prints the workflow log in the console. You will find information about the output and the internals of the
system in the comments of the script.

# Example 2 - Start a workflow via console app

`Prooph\Processing\Console` provides an easy way to start a pre configured workflow from the command line. The example
ships with such a workflow configuration. Please see [example2-workflow.config.php](config/example2-workflow.config.php).

To run the example navigate to
`<processing-package-root>/examples` and fire up
`./bin/processing collect Prooph\\ProcessingExample\\Type\\SourceUser --config-file config/example2-workflow.config.php --verbose`

## What does the command?
The command tells the processing console app that it should set up a Prooph\Processing\Environment with the config file found in `config/example2-workflow.config.php`
and pass a `collect-data` workflow message with a prototype of `Prooph\ProcessingExample\Type\SourceUser` to the workflow processor.
The console app provides three verbosity levels:

- `--quit` or  `-q` -> no output at all
- `--verbose` or `-v` -> print process log and exception traces
- no option specified -> normal verbosity including status information and exception messages

