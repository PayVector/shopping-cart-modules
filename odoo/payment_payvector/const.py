GATEWAY_DOMAIN = "payvector.net"
HOSTED_FORM_URL = "https://mms.payvector.net/Pages/PublicPages/PaymentForm.aspx"
HOSTED_FORM_RESULT_HANDLER_URL = "https://mms.payvector.net/Pages/PublicPages/PaymentFormResultHandler.ashx"

GATEWAY_ENTRY_POINTS = [
    ("https://gw1.payvector.net", 100, 2),
    ("https://gw2.payvector.net", 200, 2),
    ("https://gw3.payvector.net", 300, 2),
]
STATUS_SUCCESS = 0
STATUS_3DS_REQUIRED = 3
STATUS_REFERRED = 4
STATUS_DECLINED = 5
STATUS_DUPLICATE = 20
STATUS_FAILED = 30
DEFAULT_PAYMENT_METHOD_CODES = {"payvector"}


