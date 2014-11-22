Ginger Workflow Examples
========================

Ginger Workflow Framework ships with some examples showing you simplified workflow scenarios.

# Example 1 - Write collected data to a file

To get started you can have a look at [process-collected-data-with-linear-messaging.php](process-collected-data-with-linear-messaging.php).
The annotated source code introduces the main components of the Ginger Workflow Framework.
Of course you can also run the example. Simply open a console and navigate to the examples dir of the package then
fire up `php example1-script.php`. Please make sure that the script has read and write access
to the `data` folder otherwise you will get error messages.
The script prints the workflow log in the console. You will find information about the output and the internals of the
system in the comments of the script.

# Example 2 - Start a workflow via console app

`Ginger\Console` provides an easy way to start a pre configured workflow from the command line. The example
ships with such a workflow configuration. Please see [example2-workflow.config.php](config/example2-workflow.config.php).

To run the example (currently only possible on a *nix system) navigate to
`<ginger-package-root>/examples` and fire up
`./bin/ginger collect GingerExample\\Type\\SourceUser --config-file config/example2-workflow.config.php --verbose`

## What does the command?
The command tells the Ginger console app that it should set up a Ginger\Environment with the config file found in `config/example2-workflow.config.php`
and pass a `collect-data` workflow message with a prototype of `GingerExample\Type\SourceUser` to the workflow processor.
The console app provides three verbosity levels:

- `--quit` or  `-q` -> no output at all
- `--verbose` or `-v` -> print process log and exception traces
- no option specified -> normal verbosity including status information and exception messages

