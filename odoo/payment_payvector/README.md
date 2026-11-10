# Odoo PayVector Payment Addon

## Installation Instructions

1. **Move the module into your Odoo addons path**
   Ensure the `payment_payvector` directory is placed inside your Odoo `addons/` directory, or any other directory specified in your Odoo configuration file (`odoo.conf`) under the `addons_path` setting.

2. **Update the App List in Odoo**
   - Log in to your Odoo database as an Administrator.
   - Go to the **Apps** menu.
   - (Make sure you have "Developer Mode" enabled. If not, go to Settings and click "Activate the developer mode" at the bottom).
   - In the Apps menu, click **Update Apps List** in the top navigation bar.
   - Click **Update** in the popup dialog.

3. **Install the Module**
   - In the Apps menu, search for `PayVector`.
   - Clear the "Apps" filter in the search bar if you don't see it.
   - Click the **Activate** (or Install) button on the "PayVector Payment Provider" module.

## Configuration Instructions

Once the module is installed, you need to configure your PayVector credentials:

1. **Navigate to Payment Providers**
   - Go to **Invoicing** (or **Accounting**) > **Configuration** > **Payment Providers**.
   - (Alternatively, go to Website > Configuration > Payment Providers).
   
2. **Configure PayVector**
   - Find **PayVector** in the list and click on it.
   - Change the state from "Disabled" to **"Test Mode"** (for sandbox testing) or **"Enabled"** (for production).
   - In the **Credentials** tab, fill in your details:
     - **Merchant ID**: Your PayVector Merchant ID (e.g., `PayVec-1234567`).
     - **Password**: Your PayVector Merchant Password.
     - **Integration Mode**: 
       - Select **Hosted Payment Form** if you want customers to be redirected to the secure PayVector checkout page.
       - Select **Direct API** if you want customers to enter their card details directly on your Odoo checkout page (this mode fully supports 3D Secure v1 and v2 via inline iframes).
     
3. **Hosted Payment Form Specific Configuration**
   If you selected "Hosted Payment Form", you must also fill in:
   - **Pre-Shared Key**: The shared secret key generated in your PayVector Merchant Management System (MMS).
   - **Hash Method**: Typically `SHA1` (ensure this matches what is configured in your PayVector MMS).
   - **Result Delivery**: Typically `POST`.

4. **Publish the Provider**
   - Look for the **"Unpublished"** smart button in the top right corner of the PayVector provider screen.
   - Click it to toggle it to **"Published"** so it appears as an option during checkout for your customers.

