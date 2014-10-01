Message Component from GingerWF
===============================

This is the WorkflowMessage component for GingerWF

- File issues at https://github.com/gingerframework/gingerframework/issues
- Create pull requests against https://github.com/gingerframework/gingerframework

# Component Documentation

## Introduction

Ginger components communicate with each other via PSBs ([ProophServiceBus](https://github.com/prooph/service-bus) buses).
A PSB can be a command bus or event bus depending on the message types that are send over the bus. GingerWF provides a
universal WorkflowMessage that can be both a command or an event. It depends on the current workflow status if the message
is one type or the other. Imagine the following scenario:

A workflow processor sends a "collect user data" command to a CRM-WorkflowMessageHandler. The connector then fetches the required data
from database and uses the message to respond with the data. The command becomes a "user data collected" event, but has the
same connected ProcessId as before. With that in mind the workflow processor can easily track the status of the workflow.

## Index

- [The WorkflowMessage](docs/workflow_message.md)
- [Payload](docs/payload.md)




