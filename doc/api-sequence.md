Below is a structured **step-by-step guide** clearly explaining the **sequence** of ZATCA API calls for integrating your invoicing solution. This process ensures your solution is compliant and eligible for production.

---

## 🚩 **Overview of ZATCA API endpoints:**

| Purpose                         | API Endpoint (Sandbox)            |
| ------------------------------- | --------------------------------- |
| Compliance CSID issuance        | `/core/compliance`                |
| Invoice compliance check        | `/core/compliance/invoices`       |
| Production CSID issuance        | `/core/production/csids`          |
| Reporting (Simplified Invoices) | `/core/invoices/reporting/single` |
| Clearance (Standard Invoices)   | `/core/invoices/clearance/single` |

---

## ✅ **ZATCA Integration Process & API Call Sequence**

### ⚙️ Step 1: Generate **Compliance CSID**

**API Call:**

```
POST https://gw-fatoora.zatca.gov.sa/e-invoicing/core/compliance
```

**Required:**

* Generated CSR (**Certificate Signing Request**)
* OTP (**from ZATCA Fatoora Portal**)

**Result:**

* Compliance CSID
* Binary Security Token
* Secret Key

---

### 📜 Step 2: Perform **Invoice Compliance Checks**

Submit all invoice types to ensure compliance:

**Invoice Types:**

* Standard Invoice (`invoice`)
* Simplified Invoice (`simplified`)
* Credit Note (`standard-credit-note`, `simplified-credit-note`)
* Debit Note (`standard-debit-note`, `simplified-debit-note`)

**API Call:**

```
POST https://gw-fatoora.zatca.gov.sa/e-invoicing/core/compliance/invoices
```

**Include:**

* Invoice XML (**signed and Base64 encoded**)
* Invoice hash
* Invoice UUID

**Result:**

* Compliance confirmation from ZATCA (approval or errors)

Repeat until all invoice types pass compliance checks.

---

### 🏅 Step 3: Obtain **Production CSID**

Af successfully passing compliance checks:

**API Call:**

```
POST https://gw-fatoora.zatca.gov.sa/e-invoicing/core/production/csids
```

**Required:**

* Compliance Request ID from previous compliance step

**Result:**

* Production CSID
* Binary Security Token (Production username)
* Secret (Production password)

---

### 🖥️ Step 4: Issue and Submit **Invoices in Production Sandbox**

You now have production credentials; generate and sign invoices:

**Standard Invoices (Clearance)**

* Immediately send to ZATCA for real-time clearance.

**Simplified Invoices (Reporting)**

* Report to ZATCA within **24 hours** after issuance.

**API Calls:**

* **Standard Invoices (Clearance):**

```
POST https://gw-fatoora.zatca.gov.sa/e-invoicing/core/invoices/clearance/single
```

* **Simplified Invoices (Reporting):**

```
POST https://gw-fatoora.zatca.gov.sa/e-invoicing/core/invoices/reporting/single
```

**Include in each call:**

* Invoice XML (signed and Base64-encoded)
* Invoice hash
* UUID

**Response:**

* Standard invoice: Instant clearance approval or errors.
* Simplified invoice: Reporting confirmation or errors.

---

## 🏗️ **Complete Sandbox Testing**

* You must pass all compliance checks for each invoice type in the **sandbox**.
* After sandbox testing, request a review/validation from ZATCA.

---

## ✅ **Move from Sandbox to Production**

Once the sandbox compliance and validation is completed, request **official approval from ZATCA** to go live.

**Checklist:**

* [ ] All invoice types compliant
* [ ] Official validation from ZATCA
* [ ] Final production credentials obtained
* [ ] Live Production CSID issued by ZATCA

---

## 🎯 **Final Summary & Flow:**

1. **Generate CSR & obtain Compliance CSID** (`/core/compliance`)
2. **Invoice Compliance Checks** (`/core/compliance/invoices`)
3. **Obtain Production CSID** (`/core/production/csids`)
4. **Invoice Clearance/Reporting** (`/core/invoices/clearance/single`, `/core/invoices/reporting/single`)
5. **Final validation by ZATCA**
6. **Move to Live Production**

---

You're now fully informed on the exact sequence and API endpoints for ZATCA integration.

If you need further technical details or support in specific implementation points, let me know!
