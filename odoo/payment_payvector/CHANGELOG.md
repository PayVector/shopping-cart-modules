# PayVector Library Changelog

This document tracks all differences, bug fixes, and improvements implemented in [`payment_payvector/lib/thepaymentgateway`](file:///g:/Work/odoo/payvector/payment_payvector/lib/thepaymentgateway) compared to the base [`lib/thepaymentgateway`](file:///g:/Work/odoo/payvector/lib/thepaymentgateway).

---

## Summary of Changes

| Component / Function | Base Version (`lib/`) | Addon Version (`payment_payvector/lib/`) | Impact |
| :--- | :--- | :--- | :--- |
| **Package Initialization** | `lib/__init__.py` absent | [`payment_payvector/lib/__init__.py`](file:///g:/Work/odoo/payvector/payment_payvector/lib/__init__.py) added | Enables Python import hierarchy within Odoo addon |
| **Hash Method Normalization** | Exact string match | `.replace('-', '').replace('_', '')` | Handles variants like `HMAC-MD5` and `HMAC_SHA1` |
| **PSK Null Safety** | `str(psk).encode()` throws or hashes `"None"` | `str(psk or '').encode()` | Prevents `None` key corruption during digest calculation |
| **Hash String Sanitization** | Raw interpolation without trimming | Strips whitespace and guards `None` | Prevents hash signature mismatches caused by leading/trailing whitespace |
| **None-Safe String Formatting** | Interpolated Python `None` as literal `"None"` | Added `_v(val)` helper converting `None` to `""` | Prevents checksum failure when gateway returns empty optional fields |
| **Query String Lookup** | Case-sensitive (`CrossReference`, `OrderID`) | Case-insensitive lookup (`crossreference`, `cross_reference`, etc.) | Prevents callback errors caused by query parameter casing |
| **POST Variables Parsing** | Required all 20 fields unconditionally | Only 6 essential fields required; optional fields default to `""` | Fixes transaction rejection when address collection is disabled |
| **Card Alias Extraction** | Only checked `CardNumberLastFour` | Checks 5 common field aliases (`CardNumberLastFour`, `CardLastFour`, `CardLast4`, `LastFour`, `Last4`) | Ensures card tokenization and last-4 display always capture card info |
| **Hosted Handler Response Fallback** | Hard-fails if any field is missing | Fallback parser creates `TransactionResult` if `StatusCode` is present | Prevents valid payments from being rejected during Server Pull verification |
| **Typo Fixes** | `"payment form hander"` | `"payment form handler"` | Fixes error message consistency |

---

## Detailed Code Differences

### 1. Hash Calculation & Method Normalization
**Functions:** `calculate_hash_digest()`, `_prefix()`

- **Normalization**: Method strings are sanitized by removing hyphens and underscores (e.g., `HMAC-MD5` &rarr; `HMACMD5`, `HMAC_SHA1` &rarr; `HMACSHA1`).
- **Null Safety**: `pre_shared_key` defaults to an empty string when `None` instead of casting to `"None"`.

```diff
     @staticmethod
     def calculate_hash_digest(input_string, pre_shared_key, hash_method):
-        method = str(hash_method).upper()
+        method = str(hash_method).upper().replace('-', '').replace('_', '')
         data = str(input_string).encode()
-        key = str(pre_shared_key).encode()
+        key = str(pre_shared_key or '').encode()
         if method == 'MD5':
             return hashlib.md5(data).hexdigest()
         if method == 'SHA1':
             return hashlib.sha1(data).hexdigest()
         if method == 'HMACMD5':
             return hmac.new(key, data, hashlib.md5).hexdigest()
         if method == 'HMACSHA1':
             return hmac.new(key, data, hashlib.sha1).hexdigest()
         raise ValueError(f'Unsupported hash method: {hash_method}')

     @staticmethod
     def _prefix(psk, method):
-        return f'PreSharedKey={psk}&' if str(method).upper() in ('MD5', 'SHA1') else ''
+        m = str(method).upper().replace('-', '').replace('_', '')
+        return f'PreSharedKey={psk}&' if m in ('MD5', 'SHA1') else ''
```

---

### 2. Input Sanitization in Hash Generation
**Function:** `generate_string_to_hash3()`

- Parameter values are trimmed of leading/trailing whitespace and `None` is safely converted to empty strings.

```diff
     @staticmethod
     def generate_string_to_hash3(merchant_id, password, cross_reference, order_id, pre_shared_key, hash_method):
-        return PaymentFormHelper._prefix(pre_shared_key, hash_method) + f'MerchantID={merchant_id}&Password={password}&CrossReference={cross_reference}&OrderID={order_id}'
+        m_id = str(merchant_id or '').strip()
+        pwd = str(password or '').strip()
+        c_ref = str(cross_reference or '').strip()
+        o_id = str(order_id or '').strip()
+        psk = str(pre_shared_key or '').strip()
+        return PaymentFormHelper._prefix(psk, hash_method) + f'MerchantID={m_id}&Password={pwd}&CrossReference={c_ref}&OrderID={o_id}'
```

---

### 3. None-Safe String Formatting
**Function:** `generate_string_to_hash2()`

- Introduced internal `_v(val)` helper: ensures `None` values become `""` rather than `"None"`.
- Added `include_empty_checks=True` parameter to allow conditional inclusion of empty verification results.

```diff
     @staticmethod
-    def generate_string_to_hash2(merchant_id, password, tr, pre_shared_key, hash_method):
-        s = PaymentFormHelper._prefix(pre_shared_key, hash_method) + f'MerchantID={merchant_id}&Password={password}&StatusCode={tr.status_code}&Message={tr.message}&PreviousStatusCode={tr.previous_status_code}&PreviousMessage={tr.previous_message}&CrossReference={tr.cross_reference}'
+    def generate_string_to_hash2(merchant_id, password, tr, pre_shared_key, hash_method, include_empty_checks=True):
+        def _v(val):
+            return "" if val is None else str(val)
+
+        psk = str(pre_shared_key or '').strip()
+        prefix = PaymentFormHelper._prefix(psk, hash_method)
+        m_id = _v(merchant_id).strip()
+        pwd = _v(password).strip()
+
+        s = (
+            prefix
+            + f"MerchantID={m_id}&Password={pwd}"
+            + f"&StatusCode={_v(tr.status_code)}&Message={_v(tr.message)}"
+            + f"&PreviousStatusCode={_v(tr.previous_status_code)}&PreviousMessage={_v(tr.previous_message)}"
+            + f"&CrossReference={_v(tr.cross_reference)}"
+        )
+        has_an = (tr.address_numeric_check_result is not None) if include_empty_checks else (tr.address_numeric_check_result not in (None, ""))
         if has_an:
-            s += f'&AddressNumericCheckResult={tr.address_numeric_check_result}&PostCodeCheckResult={tr.post_code_check_result}'
+            s += f"&AddressNumericCheckResult={_v(tr.address_numeric_check_result)}&PostCodeCheckResult={_v(tr.post_code_check_result)}"
         ...
```

---

### 4. Case-Insensitive Query Variable Lookup
**Function:** `get_transaction_reference_from_query_string()`

- Handles varying case representations returned in HTTP GET callbacks.

```diff
     @staticmethod
     def get_transaction_reference_from_query_string(v):
-        missing = [k for k in ('CrossReference', 'OrderID') if k not in v]
-        return (not missing, v.get('CrossReference'), v.get('OrderID'), v.get('HashDigest', ''), ', '.join((f'Expected variable [{k}] not received' for k in missing)))
+        v_ci = {str(k).lower(): val for k, val in v.items()} if isinstance(v, dict) else {}
+        cross_ref = v.get('CrossReference') or v_ci.get('crossreference') or v_ci.get('cross_reference')
+        order_id = v.get('OrderID') or v_ci.get('orderid') or v_ci.get('order_id')
+        digest = v.get('HashDigest') or v_ci.get('hashdigest') or v_ci.get('hash_digest') or ''
+        missing = []
+        if not cross_ref:
+            missing.append('CrossReference')
+        if not order_id:
+            missing.append('OrderID')
+        return (not missing, cross_ref, order_id, digest, ', '.join(f'Expected variable [{k}] not received' for k in missing))
```

---

### 5. Resilient POST Variables & Card Alias Extraction
**Function:** `get_transaction_result_from_post_variables()`

- **Relaxed required fields**: Separates 20 fields into essential keys (`StatusCode`, `Message`, `CrossReference`, `Amount`, `CurrencyCode`, `OrderID`). Missing optional keys default to `""`.
- **Safe numeric casting**: Checks `.isdigit()` before integer conversion to prevent unhandled exceptions.
- **Card aliases**: Resolves last 4 digits from various PayVector response formats.

```diff
     @staticmethod
     def get_transaction_result_from_post_variables(v):
+        if not isinstance(v, dict):
+            return (False, None, '', 'Invalid post variables format')
+        v_ci = {str(k).lower(): val for k, val in v.items()}
         required = [
             'StatusCode', 'Message', 'PreviousStatusCode', 'PreviousMessage',
             'CrossReference', 'Amount', 'CurrencyCode', 'OrderID',
             'TransactionType', 'TransactionDateTime', 'OrderDescription',
             'CustomerName', 'Address1', 'Address2', 'Address3', 'Address4',
             'City', 'State', 'PostCode', 'CountryCode'
         ]
-        missing = [k for k in required if k not in v]
-        if missing:
-            return (False, None, v.get('HashDigest', ''), ', '.join((f'Expected variable [{k}] not received' for k in missing)))
+        essential = ['StatusCode', 'Message', 'CrossReference', 'Amount', 'CurrencyCode', 'OrderID']
+        missing_essential = [k for k in essential if k not in v and k.lower() not in v_ci]
+        if missing_essential:
+            return (False, None, v.get('HashDigest', '') or v_ci.get('hashdigest', ''), ', '.join(f'Expected variable [{k}] not received' for k in missing_essential))
...
+        # Explicit check for card aliases
+        if not getattr(tr, 'card_number_last_four', None):
+            last4_alias = (
+                v.get('CardNumberLastFour')
+                or v_ci.get('cardnumberlastfour')
+                or v.get('CardLastFour')
+                or v_ci.get('cardlastfour')
+                or v.get('CardLast4')
+                or v_ci.get('cardlast4')
+                or v.get('LastFour')
+                or v_ci.get('lastfour')
+                or v.get('Last4')
+                or v_ci.get('last4')
+            )
+            if last4_alias:
+                tr.card_number_last_four = str(last4_alias)
```

---

### 6. Payment Form Handler Fallback & Typo Fix
**Function:** `get_transaction_result_from_payment_form_handler()`

- Fixed typo `"payment form hander"` &rarr; `"payment form handler"`.
- Trims whitespace on POST payload.
- Added fallback: if POST parsing fails but `StatusCode` is returned, a valid `TransactionResult` is still synthesized.

```diff
     @staticmethod
     def get_transaction_result_from_payment_form_handler(url, merchant_id, password, cross_reference, timeout=30):
-        data = urlencode({'MerchantID': merchant_id, 'Password': password, 'CrossReference': cross_reference}).encode()
+        data = urlencode({'MerchantID': str(merchant_id).strip(), 'Password': str(password).strip(), 'CrossReference': str(cross_reference).strip()}).encode()
         with urlopen(Request(url, data=data, method='POST'), timeout=timeout) as r:
             response = r.read().decode()
         if not response:
-            return (False, None, 'Received empty response from payment form hander')
+            return (False, None, 'Received empty response from payment form handler')
         parsed = PaymentFormHelper.parse_name_value_string_into_array(response, True)
         if int(parsed.get('StatusCode', '-1')) != 0:
-            return (False, None, parsed.get('Message', f'Received invalid response from payment form hander [{response}]'))
+            return (False, None, parsed.get('Message', f'Received invalid response from payment form handler [{response}]'))
         if 'TransactionResult' not in parsed:
-            return (False, None, f'No transaction result in response from payment form hander [{response}]')
-        result_vars = PaymentFormHelper.parse_name_value_string_into_array(unquote(parsed['TransactionResult']), False)
-        ok, tr, _, msg = PaymentFormHelper.get_transaction_result_from_post_variables(result_vars)
+            return (False, None, f'No transaction result in response from payment form handler [{response}]')
+        result_vars = PaymentFormHelper.parse_name_value_string_into_array(unquote(parsed['TransactionResult']), True)
+        ok, tr, dummy_hash, msg = PaymentFormHelper.get_transaction_result_from_post_variables(result_vars)
+        if not ok and 'StatusCode' in result_vars:
+            try:
+                sc = int(result_vars.get('StatusCode', '-1'))
+            except (ValueError, TypeError):
+                sc = -1
+            if sc >= 0:
+                ...
+                return (True, tr, '')
         return (ok, tr, msg)
```
