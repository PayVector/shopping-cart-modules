import os
import sys
_lib_path = os.path.join(os.path.dirname(__file__), "lib")
if _lib_path not in sys.path:
    sys.path.insert(0, _lib_path)

from . import controllers, models
from odoo.addons.payment import reset_payment_provider, setup_provider


def post_init_hook(env):
    setup_provider(env, "payvector")
    providers = env["payment.provider"].search([("code", "=", "payvector")])
    providers._sync_payvector_form_views()
    try:
        with env.cr.savepoint():
            env.cr.execute("DROP TABLE IF EXISTS payvector_hpf_server_results CASCADE;")
    except Exception:
        pass


def uninstall_hook(env):
    reset_payment_provider(env, "payvector")

