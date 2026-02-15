To onboard multiple Electronic Generation Solution (EGS) units under the same VAT number (e.g., `222223333344444`) for your business, you need to assign unique identifiers to each unit. This ensures compliance with ZATCA's e-invoicing regulations.

### 🔢 Assigning Unique Identifiers to Multiple EGS Units

Each EGS unit must have distinct values for the following fields during the onboarding process:

* **`common_name`**: A unique name for the EGS unit.([LinkedIn][1])

* **`egs_serial_number`**: A unique serial number for the EGS unit.

Other fields like `organization_identifier`, `organization_name`, `organization_unit`, `country`, `address`, `business_category`, `invoice_type`, `egs_solution_name`, and `egs_model` can remain the same across all units if they pertain to the same business entity.

### 🧾 Example Configuration for Three EGS Units

Here's how you might configure three EGS units under the same VAT number:([Fatoora Developer Community][2])

```json
[
  {
    "common_name": "EGS-Unit-1",
    "organization_identifier": "399999999900003",
    "organization_name": "Maximum Speed Tech Supply LTD",
    "organization_unit": "7028000999",
    "country": "SA",
    "address": "Riyadh Saudi Arabia",
    "business_category": "Tech IT",
    "invoice_type": "1100",
    "egs_solution_name": "Speed-Tech-EGS",
    "egs_model": "GSP-Model-1",
    "egs_serial_number": "GSP-SN001"
  },
  {
    "common_name": "EGS-Unit-2",
    "organization_identifier": "399999999900003",
    "organization_name": "Maximum Speed Tech Supply LTD",
    "organization_unit": "7028000999",
    "country": "SA",
    "address": "Riyadh Saudi Arabia",
    "business_category": "Tech IT",
    "invoice_type": "1100",
    "egs_solution_name": "Speed-Tech-EGS",
    "egs_model": "GSP-Model-1",
    "egs_serial_number": "GSP-SN002"
  },
  {
    "common_name": "EGS-Unit-3",
    "organization_identifier": "399999999900003",
    "organization_name": "Maximum Speed Tech Supply LTD",
    "organization_unit": "7028000999",
    "country": "SA",
    "address": "Riyadh Saudi Arabia",
    "business_category": "Tech IT",
    "invoice_type": "1100",
    "egs_solution_name": "Speed-Tech-EGS",
    "egs_model": "GSP-Model-1",
    "egs_serial_number": "GSP-SN003"
  }
]
```

### 📌 Important Notes

* **Unique Identifiers**: Ensure that both `common_name` and `egs_serial_number` are unique for each EGS unit to prevent conflicts during the onboarding process.

* **Onboarding Process**: Each EGS unit must be individually onboarded through the FATOORA Portal, providing the unique identifiers mentioned above.([ALZERP][3])

* **Compliance**: Properly distinguishing each EGS unit ensures compliance with ZATCA's regulations and facilitates accurate tracking and reporting of invoices.

By following this approach, you can effectively manage multiple EGS units under the same VAT number, maintaining compliance and operational efficiency.

If you need further assistance with the onboarding process or have additional questions, feel free to ask!
