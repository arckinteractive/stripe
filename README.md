Stripe
======

Stripe API Layer for Elgg

The plugin is intended primarily for developers, who are implementing a payments
flow.

## Intro

This plugin implements an API Layer for interfacing with Elgg. It implements
most common methods, including creating and updating customers, creating charges,
adding and removing cards etc.

The goal is to provide a uniform API that works with Elgg's entity architecture,
this includes maintaining references between, for example, Elgg users and Stripe
customers.

The plugin also provides a UI for users to manage their payment methods, view
their transaction history, etc.

Architecture is such as to avoid storing data in Elgg, where possible, so your
site stays PCI compliant, while entertaining broad e-commerce possibilities.



## Webhooks

To ensure that Elgg receives some crucial updates, please set up your Stripe
webhooks as follows:

**Testing**
https://YOUR-SITE/services/api/rest/json?method=stripe.webhooks&environment=sandbox

**Live**
https://YOUR-SITE/services/api/rest/json?method=stripe.webhooks&environment=production


Once you have set up the webhooks, you can add handlers for ```$stripe_event_type, 'stripe.events'```
plugin hook in Elgg to implement additional logic. Your callback function will
receive a Stripe event object and an environment descriptor.

A list of Stripe events can be found here:
https://stripe.com/docs/api#event_types

