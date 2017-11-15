### What is Mapp Force?

* * *

##### About

Mapp Force is an open-source application developed by Mapp Digital which facilitates transfer of data between the Mapp Customer Engagment Platform (CEP) and Salesforce CRM.

The application allows the user to configure which data is transfered between the two systems and how are Mapp CEP attributes mapped to corresponding Salesforce fields.

##### Example

For illlustration, consider an event occuring in your Mapp CEP system, such as an Email Message is opened by a Contact, a Contact entering a Group or its Profile attribute changing. Using Automations feature of Mapp CEP, you are able to fire an outgoing HTTP request to selected Mapp Force endpoints with information about this event and the Contact by including a message body in a defined format. Have you configured your attribute [Mapping]({{base_url}}/admin/mapping) and authorized MappForce to access your Salesforce in the [Settings]({{base_url}}/admin/settings) section, Mapp Force connects to your Salesforce instance and performs a data operation defined by the endpoint, commonly an insert or update of the corresponding record.

##### Contents

*   <span>[1\. Requirements](#requirements)</span>
*   [2\. Configuration](#configuration)
*   [3\. Operation](#operation)
*   <span>[4\. Security](#security)</span>

</div>

</div>

<div class="mdl-card mdl-shadow--2dp ui-page-card">

<div class="mdl-card__supporting-text">

### 1\. Requirements

* * *

Make sure all the below requirements are met before you start configuring your Mapp Force instance.

#### 1.1\. Mapp Customer Engagement Platform

##### REST API 2.0

REST API allows that data is brought over from Salesforce to Mapp CEP and back. In addition to this, MappForce uses the REST API for user management.

##### Automations

Automations or Automation Whiteboards allow you to fire a event-based job anytime an event which you want to mirror in Salesforce has occured. Using Automations, you will be able to make a request to the API layer of MappForce from Mapp CEP.

##### Transport Security Record

This allows that an HTTP Basic Authorization header can be added to the HTTP requests coming from Mapp CEP. MappForce API layer expects that user authentication details arrive in this header. Transport security has to be enabled by your Account Manager or Mapp Customer Support Service.

##### IP Whitelisting

The IP of the server, where your instance of MappForce is deployed has to be whitelisted to access the Mapp CEP. This configuration is only relevant if your Mapp Force instance is self-hosted on one of your proprietary servers or hosting platforms like Heroku, Digital Ocean, AWS, ...

#### 1.2\. Salesforce

* * *

##### Connected App

It is recommended to authenticate against your Salesforce CRM instance using an OAuth flow. MappForce supports the OAuth flow, but requires that you store your consumer key, consumer secret and refresh token in its database. All sensitive data is encrypted before storing. You should however always have this security aspect in mind when deploying and sharing access to the application. See the Configuration section for details on how to set up your Connected App in Salesforce.

##### System Administrator Profile

While some Mapp Force functionality may be available even for lower level roles, a System Administrator profile authorizing the App ensures that you will be able to retrieve your object definitions like custom fields, perform edits and deletions to all records and send email notifications.

##### SOAP API enabled with sufficient limits

All operations in Salesforce are performed via its SOAP API Client. Even when your API is enabled, make sure that your API Limits are sufficent, especially if planning to transfer email interactions.

##### Marketing User

Your Profile should have the Marketing User checkbox checked, or the corresponding privileges for creating campaigns enabled.

</div>

</div>

<div class="mdl-card mdl-shadow--2dp ui-page-card">

<div class="mdl-card__supporting-text">

### 2\. Configuration

* * *

#### 2.1\. Build Salesforce Connection

* * *

Before using it for data transfer, you must ensure that your MappForce app is able to access your Salesforce CRM at all times. A need to manually re-authorize MappForce's access in production may lead to data loss. Follow the below steps to estabilish a connection.

##### Create a Connected App

Creation of the Connected App is the essential first step in building a Salesforce connection. Follow the tutorial [here](https://developer.salesforce.com/page/Connected_Apps).

![]({{base_url}}/assets/img/mapp_force_policy.PNG)

##### Store Connected App details in MappForce

Go to Settings, select the Salesforce tab and populate the following fields: Consumer Key, Consumer Secret and Callback URL with values obtained from your Connected App.

##### Authorize App

Once you have stored your Connected App details into MappForce database, you will be able to click on the 'Authorize App' button. This will trigger the authorization process and you will be redirected your first to the Salesforce login screen and later to an Authorization page and from there to the Callback URL defined above. If the authorization has succeeded, you will arrive back to the Settings page and MappForce will present a success message.

##### Test Connection

After a succesful authorization, MappForce will show a new 'Test Connection' button in the Settings->Salesforce tab. Clicking the button, MappForce will attempt to connect to Salesforce and return the details of the authenticated user on whose behalf MappForce is accessing Salesforce.

Your Redirect URL (also called Callback URL) has to be hosted on a secure domain (https://...). Typically, when this would look something like:

<pre>https://YOUR-SERVER-NAME.com/mappforce/settings/oauth</pre>

or if MappForce is hosted on platforms such as Heroku, it will be this:

<pre>https://YOUR-APP-NAME.herokuapp.com/settings/oauth</pre>

This is the URL you will be redirected to after a succesful authorization of the MappForce App by Salesforce.

#### 2.2\. Settings - Mapp CEP

* * *

The Mapp CEP contains a number of useful configuration options for the data transfer. While none of these are essential for the functioning of MappForce, future releases will make use of f.e. group configuration templates, default values and country and language transformations.

#### 2.3\. Mapping - Leads and Contacts

* * *

The Mapping section allows you to map attributes defined in your Mapp CEP instance with the existing fields in your Salesforce CRM instance. This mapping can differ for Salesforce CRM Lead and Contact entities. There are three kinds of attributes you can map:

##### Standard Attributes

Standard Attributes are identical for every instance of Mapp CEP and you will not be able to change them. You can however map them to any Salesforce fields. Your mapping has to include at least one of the three allowed unique identifiers for data transfer. These are **user.Email, user.Identifier or user.MobileNumber**. Other identifiers may be considered in future releases.

##### Custom Attributes

Custom Attributes are created by Mapp CEP users and are sourced after the user has logged into Mapp Force. Therefore, you will see the up-to-date state of your Custom Attributes, ready for mapping anytime you log in.

##### Member Attributes and Custom Values

Member Attributes only exist in a Mapp CEP Group membership context - a CEP Contact can have multiple member attributes of the same name in multiple groups, but their values can be different in every group the Contact is a member of. Due to this, the definition of mapping for member attributes is different from the Standard and Custom Attribute. To make use of a member attribute in MappForce, you need to register it yourself in the Mapping->Member Attributes section and map it to existing Salesforce fields, one by one. Effectively, this allows you to name your Member attributes any way you want - with the exception of currently existing mappings.

#### 2.4\. Mapping - Campaign

* * *

Although the closest entity to Salesforce Campaign is the Mapp CEP Group, the mapping is not limited to selected Group Settings and all custom Group Attributes, but also, just like in the above case for Member Attributes, allows you to populate custom fields on the Campaign with user-defined data. As the attributes are group-specific, Mapp Force cannot preload them for mapping like in the case of Standard and Custom Attributes.

</div>

</div>

<div class="mdl-card mdl-shadow--2dp ui-page-card">

<div class="mdl-card__supporting-text">

### 3\. Operation

* * *

<span>To transfer data between your Mapp CEP and Salesforce, you need to make use of the MappForce API layer. This section describes how to access and complete the most common cases.</span>

#### 3.1\. Accessing MappForce API

* * *

<span>Testing the installation and performance is essential to avoid oversights or errors in your Mapping definitions and unwanted behavior of MappForce such as sending of Email notifications or reassignment of Salesforce records.</span>

##### Authentication

<span class="mdl-list__item-text-body">MappForce API layer uses HTTP Basic Authentication. To access the API, use the same credentials that you have used to login to the Admin section. The authentication must follow the below pattern:

<pre>mapp_system_instance|username:password</pre>

For example:

<pre>mapp_marketing|my.api.user@mapp.com:mysecretpassword123</pre>

An authenticated request will look like this:

<pre>$ curl -u mapp_marketing|my_api.user@mapp.com:mysecretpassword123 https://YOUR-SERVER-NAME.com/mappforce/api/ </pre>

</span>

##### First Call

Assuming your deployment looks like the below:

<pre>https://YOUR-SERVER-NAME.com/mappforce/</pre>

You will be able to send a HTTP GET request to the root endpoint of the MappForce API:

<pre>https://YOUR-SERVER-NAME.com/mappforce/api</pre>

A request to the root endpoint offers only an overview of API methods and a welcome message. An example response is below:

<pre>{"error":false,"error_message":"","payload":["Welcome to MappForce, these are the supported API endpoints",{"method":"GET","path":"\/api\/"},{"method":"GET","path":"\/api\/mapping"}, ... ,{"method":"POST","path":"\/api\/sfdc\/{object}\/create"}]}</pre>

#### 3.2\. Creating a Lead

* * *

To create a Lead, you will need have completed 3 steps:

*   1\. Succesfully estabilish a connection to your Salesforce instance by completing the OAuth procedure
*   2\. Access the MappForce API layer
*   3\. Generate a JSON Map, using a dedicated button in the Mapping section

A simple example of a JSON Map can look like the one below:

<pre>'{
  "user.Email": "${user.Email}",
  "user.FirstName": "${user.FirstName}",
  "user.LastName": "${user.LastName}",
  "user.ISOCountryCode": "${user.ISOCountryCode}",
  "user.CustomAttribute.Company": "${user.CustomAttribute['Company']}",
  "user.CustomAttribute.Job": "${user.CustomAttribute['Job']}"
}'</pre>

Note that the part enclosed in curly brackets, starting with a dollar sign ,f.e. "${user.Email}" is a placeholder for Mapp CEP. For testing purposes you can replace the placeholders with some dummy values for your new Lead. Please note that your Mapping should respect the requiered values, restricted picklists and other customizations of your Salesforce objects.

<pre>'{
  "user.Email": "darth.vader@galactic-empire.com",
  "user.FirstName": "Darth",
  "user.LastName": "Vader",
  "user.ISOCountryCode": "US",
  "user.CustomAttribute.Company": "Galactic EMpire",
  "user.CustomAttribute.Job": "Sith Lord"
}'</pre>

With the above JSON Map stored in a file named data.json, you can now launch the request to a MappForce endpoint like this:

<pre>$ curl -d @my/folder/data.json -u mapp_marketing|my_api.user@mapp.com:mysecretpassword123 -H 'Accept: application/json' -X POST https://YOUR-SERVER-NAME.com/mappforce/api/sfdc/lead/create </pre>

If succesful, MappForce will collect the response from Salesforce and pass it over to you in a JSON formatted response:

<pre>{"error":false,"error_message":"","payload":{"errors":[null],"id":"00Q6100000Ow25nEAB","success":true}}</pre>

#### 3.3\. Upserting a Lead

* * *

The most versatile method of MappForce is the upsert request. The upsert request chains multiple other requests together to find out the best thing to do. Imagine a case when you are not sure whether your Mapp CEP contact is already recorded in your Salesforce CRM and if so, whether it is a Contact, or a Lead. The upsert method does the following:

*   1\. Search for matching records using the Email address.
*   2\. If a Contact is found, this Contact is updated with incoming request data. If a Lead is found, this Lead is updated.
*   3\. If no matching records are found, a new Lead is created. It is the responsibility of the user to make sure all required fields for Lead creation are included in the JSON Map.

A request body of an upsert call looks exactly like the one used above for a create call.

Note that the Request URL below does not cite any specific Salesforce object attributes due to the fact that either a Lead or Contact record will be created/updated.

<pre>$ curl -d @my/folder/data.json -u mapp_marketing|my_api.user@mapp.com:mysecretpassword123 -H 'Accept: application/json' -X POST https://YOUR-SERVER-NAME.com/mappforce/api/sfdc/upsert?identifier=email </pre>

Success Response returns record details. See the next section to understand campaign and campaign_member response nodes.

<pre>{"error":false,"error_message":"","payload":{"record":{"errors":[null],"id":"00Q6100000Ow25nEAB","success":true},"campaign":null,"campaign_member":null}</pre>

#### 3.4\. Associating with a Salesforce Campaign

* * *

Upsert method, when provided with data, will also attempt to associate the record (Lead or Contact) with a Campaign record specified in the JSON Map. The two records are associated using a help object called Campaign Member. The steps of associating a Lead or a Contact with a Campaign are the following:

*   1\. Search for a Campaign record by its Name
*   2\. If a Campaign is found, update with other data, create a Campaign Member with a defined Status.
*   3\. If no matching Campaign record is found, both Campaign and Campaign Member records are created. It is the responsibility of the user to make sure all required fields for Campaign creation are included in the JSON Map.

A request body of an upsert call looks exactly like the one used above for a create call.

<pre>'{
    "user.Email": "darth.vader@galactic-empire.com",
    "user.CustomAttribute.Job": "Sith Lord",
    "user.CustomAttribute.Company": "Galactic Empiry",
    "user.LastName": "Vader",
    "user.FirstName": "Darth",
    "campaign": {
        "group.Name": "Death Star Campaign",
        "status": "Responded"
    }
}'</pre>

The request with

<pre>$ curl -d @my/folder/data.json -u mapp_marketing|my_api.user@mapp.com:mysecretpassword123 -H 'Accept: application/json' -X POST https://YOUR-SERVER-NAME.com/mappforce/api/sfdc/upsert?identifier=email </pre>

Success Response

<pre>{"error":false,"error_message":"","payload":{"record":{"errors":[null],"id":"00Q6100000Ow25nEAB","success":true},"campaign":"campaign":{"errors":[null],"id":"70161000000844BAAQ","success":true},"campaign_member":{"errors":[null],"id":"00Q6100000Ow25nEAB","success":true}}}</pre>

#### 3.5\. Setup first automation

* * *

You are now familiar with the upsert request and the JSON Map object in MappForce. Below are the steps to setup a first automated feed from your Mapp CEP to your Salesforce CRM. In this example, we will consider a CEP Contact opening an Email message. We will use the event-based Whiteboard to transfer this event and related Contact data to Salesforce using the upsert method. We will use the Message Name placeholder to associate this Contact with a Campaign. This step-by-step manual may differ based on latest developments in Mapp CEP. It is expected that you have defined a Transport Security Record in the correct format before creating an automation.

*   1\. Go to your Mapp CEP and open the Automations section.
*   2\. Click on Whiteboard NEW, further click on Create and selec the Event-Based Whiteboard.
*   3\.

A request body of an upsert call looks exactly like the one used above for a create call.</div>

</div>

<div class="mdl-card mdl-shadow--2dp ui-page-card">

<div class="mdl-card__supporting-text">

### 4\. Security

* * *

#### 4.1\. Storing sensitive information

* * *

##### Access and Refresh Tokens

To be able to connect to your Salesforce instance at any time without a need to re-authorize MappForce access by a human user, the application is using a so-called Refresh token. MappForce requires this token not to expire unless explicitly revoked. Refresh token combined with information from your Connected App (Consumer Key and Consumer Secret) is used to obtain a short-lived Access Token. Access Token is used to create and update records.

##### Storage and Encryption

Tokens and Connected App details are needed periodically to request a new Access Token, whenever an old Access Token expires. To be able to do this, all of the above information is stored in a SQLite database as a part of MappForce. The details are encrypted so that if the database is compromised, the attacker cannot make use of any of them.

#### 4.2\. Safe deployment

* * *

##### Secure protocol

As authentication method against the MappForce API layer is HTTP Basic, sending and receving servers have to communicate over a secure protocol to keep password safe during transfer.

##### Securing your file-system

An attacker with access to your file-system will be able to collect and decrypt the access tokens, but will not get hold of any usernames or passwords of Salesforce users. In case of such event, revoking the access of your Connected App is the best counter-measure. MappForce is designed only to create and update certain record types (Leads, Contacts and Campaigns), but an attacker with an Access Token will be able to make any requests against your Salesforce API.

