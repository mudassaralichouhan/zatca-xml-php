```json
{
  "invoice_id": 1,
  "uuid": "bf8e9509-fb0a-4bc8-9f95-8ee621f0508b",
  "note": "ABC",
  "note_language_id": "ar",
  "issue_date": "2025-05-13",
  "issue_time": "17:41:08",
  "currency_code": "SAR",
  "payment_means_code": "bank_card", // 'cash' => '10', 'credit' => '30', 'bank_account' => '42', 'bank_card' => '48'
  "invoice_sub_type": {
    "simplified": true, // pass false if standard
    "third_party": false,
    "nominal": false,
    "exports": false,
    "summary": false,
    "self_billed": false
  },
  "invoice_type": "invoice", // 'invoice' => '388', 'debit' => '383', 'credit' => '381'
  "instruction_note": "Refunded because customer ordered by mistake", // pass when in (debit, credit)
  "billing_reference": { // pass when in (debit, credit) otherwise null
    "invoice_id": "SIM20250510-0059139",
    "issue_date": "2025-05-08"
  },
  "accounting_supplier_party": {
    "postal_address": {
      "street_name": "شارع خالد بن الوليد",
      "building_number": "2568",
      "plot_identification": "6273",
      "city_subdivision_name": "الشرقية",
      "city_name": "الطائف",
      "postal_zone": "26523",
      "country_subentity": null,
      "additional_street_name": null
    },
    "party_identification": {
      "id": "324223432432432"
    }
  },
  "accounting_customer_party": {
    "postal_address": {
      "street_name": "طريق الملك فهد",
      "building_number": "5566",
      "plot_identification": "7890",
      "city_subdivision_name": "حي العليا",
      "city_name": "الرياض | Riyadh",
      "postal_zone": "11564",
      "country_subentity": "منطقة الرياض",
      "additional_street_name": "بجوار البنك الأهلي",
      "country_identification_code": "SA"
    },
    "party_tax_scheme": {
      "company_id": "310123456700003"
    },
    "party_legal_entity": {
      "registration_name": "مؤسسة العالم الفني الامنية"
    },
    "party_identification": {
      "id": "987650000098765" // https://zatca1.discourse.group/t/br-ksa-f-07-the-value-provided-in-other-buyer-id-bt-46-for-scheme-id-tin-appears-to-be-incorrect/8156/17
    }
  },
  "delivery": { // pass only when standard
    "actual_delivery_date": "2025-05-08"
  },
  "allowance_charges": [
    {
      "charge_indicator": false,
      "allowance_charge_reason": "discount",
      "amount": "0.00",
      "currency_id": "SAR",
      "tax_categories": [
        {
          "tax_percent": "15"
        },
        {
          "tax_percent": "15"
        }
      ]
    }
  ],
  "tax_totals": [
    {
      "tax_amount": "30.15"
    },
    {
      "tax_amount": "30.15",
      "tax_subtotal_amount": "30.15",
      "taxable_amount": "201.00",
      "tax_category": {
        "tax_percent": "15.00"
      }
    }
  ],
  "legal_monetary_total": {
    "line_extension_amount": "201.00",
    "tax_exclusive_amount": "201.00",
    "tax_inclusive_amount": "231.15",
    "allowance_total_amount": "0.00",
    "prepaid_amount": "0.00",
    "payable_amount": "231.15"
  },
  "invoice_lines": [
    {
      "invoiced_quantity": "33.000000",
      "invoiced_quantity_unit_code": "PCE",
      "line_extension_amount": "99.00",
      "tax_total": {
        "tax_amount": "14.85",
        "rounding_amount": "113.85"
      },
      "item": {
        "name": "كتاب"
      },
      "price": {
        "price_amount": "3.00",
        "allowance_charge": {
          "charge_indicator": false,
          "allowance_charge_reason": "discount",
          "amount": "0.00",
          "currency_id": "SAR",
          "tax_category": null,
          "add_tax_category": false,
          "add_id": false,
          "tax_categories": [],
          "index": null
        }
      }
    },
    {
      "invoiced_quantity": "3.000000",
      "invoiced_quantity_unit_code": "PCE",
      "line_extension_amount": "102.00",
      "tax_total": {
        "tax_amount": "15.30",
        "rounding_amount": "117.30"
      },
      "item": {
        "name": "قلم"
      },
      "price": {
        "price_amount": "34.00",
        "allowance_charge": {
          "charge_indicator": false,
          "allowance_charge_reason": "discount",
          "amount": "0.00",
          "currency_id": "SAR",
          "tax_category": null,
          "add_tax_category": false,
          "add_id": false,
          "tax_categories": [],
          "index": null
        }
      }
    }
  ]
}
```