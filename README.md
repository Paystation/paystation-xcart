# Paystation payment module for X-Cart

This integration is currently only tested up to X-Cart 5.0.13

## Requirements
* An account with [Paystation](https://www2.paystation.co.nz/)

## Installation

These instructions will guide you through installing the module and conducting a test transaction.

After you have correctly installed X-Cart:

1. Access the administration pages of your X-cart site.

2. Select 'Installed Modules' from the Extensions menu (in the top right-hand corner).

3. Click the "Upload add-on button", and browse to select the .tar file these instructions are contained in.

4. Click "Install add-on". X-cart will then re-build the cache.

5. When this has finished, the "Recently installed modules" page will appear. This list should include the `Paystation three-party payment module`

6. Select 'Payment Methods' from the 'Store Setup' menu.

7. In the 'Accepting credit cards online' panel, click the 'Add payment method' button.

8. Select the "Payment gateways" tab, and scroll down to find the 'Paystation three-party payment module'. Click the Choose button next to this.

9. The settings page for the payment module will appear.

Note: In steps 10 and 11, be careful not to leave trailing and leading spaces in the input fields.

10. Set 'Paystation ID' to the Paystation ID provided.

11. Set 'Paystation Gateway' to the Gateway ID provided.

12. Leave the 'Test/Live mode' at 'Test' for the meantime.

13. Click the Update button.

14. The Paystation three-party payment module will now appear in the
'Accepting credit cards online' panel. On the left of its entry in the list,
there is a greyed-out power on/off button. Click this to enable the payment
method. The button will turn green when it is enabled.

15. The return URL is: `[host + X-Cart directory]/?target=payment_return&txn_id_name=txnId` For example - `www.yourwebsite.co.nz/x-cart/?target=payment_return&txn_id_name=txnId`

17. Send the return URL and your Paystation ID to support@paystation.co.nz to request your Return URL to be updated.

18. Send your server's IP addresses to support@paystation.co.nz, this module uses the Remote Lookup (Quick) Interface API which is IP limited.

19. Go to your online store.

20. To do a successful test transaction, make a purchase where the final
cost will have the cent value set to .00, for example $1.00, this will
return a successful test transaction. To do an unsuccessful test transaction
make a purchase where the final cost will have the cent value set to
anything other than .00, for example $1.01-$1.99, this will return an
unsuccessful test transaction.

Important: You can only use the test Visa and Mastercards supplied by Paystation for test transactions. They can be found here [Visit the Test Card Number page](https://www2.paystation.co.nz/developers/test-cards/).

21. When you go to checkout - make sure you choose Paystation Payment Gateway in the Payment method section.

22. If everything works ok, go back to the 'Payment Methods' page, find the Paystation module, and click the Configure link.

23. Change the mode from 'Test' to 'Live', and click the Update button

24. Fill in the form found on https://www2.paystation.co.nz/go-live so that Paystation can test and set your account into Production Mode.

25. Congratulations - you can now process online credit card payments
