Message Component from Prooph Processing
===============================

This is the WorkflowMessage component for prooph processing

- File issues at https://github.com/prooph/processing/issues
- Create pull requests against https://github.com/prooph/processing

# Component Documentation

## Introduction

Processing components communicate with each other via PSBs ([ProophServiceBus](https://github.com/prooph/service-bus) buses).
A PSB can be a command bus or event bus depending on the message types that are send over the bus. Processing provides a
universal WorkflowMessage that can be both a command or an event. It depends on the current workflow status if the message
is one type or the other. Imagine the following scenario:

A workflow processor sends a "collect user data" command to a CRM-WorkflowMessageHandler. The connector then fetches the required data
from database and uses the message to respond with the data. The command becomes a "user data collected" event, but has the
same connected ProcessId as before. With that in mind the workflow processor can easily track the status of the workflow.

## Index

- [The WorkflowMessage](docs/workflow_message.md)
- [Payload](docs/payload.md)




