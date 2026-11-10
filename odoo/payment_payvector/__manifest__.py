{
    "name": "PayVector Payment Provider",
    "summary": "PayVector Payment Gateway for Odoo",
    "description": " ",  # Non-empty string to avoid loading the README file.
    "category": "Accounting/Payment Providers",
    "sequence": 650,
    "author": "PayVector",
    "website": "https://www.payvector.net",
    "version": "2.0",
    "license": "LGPL-3",
    "depends": ["payment"],
    "data": [
        "security/ir.access.csv",
        "views/payment_payvector_templates.xml",
        "views/payment_provider_views.xml",
        "data/payment_provider_data.xml",
        "data/payment_method_data.xml",
    ],
    "assets": {
        "web.assets_frontend": [
            "payment_payvector/static/src/interactions/**/*",
        ],
    },
    "post_init_hook": "post_init_hook",
    "uninstall_hook": "uninstall_hook",
    "installable": True,
    "application": False,
    "auto_install": False,
}
