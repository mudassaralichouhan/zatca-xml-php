<?php

require ROOT_PATH . '/src/templates/invoice_billing_reference_template.php';

/**
 * Maybe use a templating engine instead of str replace.
 * This works for now though
 *
 * cbc:InvoiceTypeCode: 388: BR-KSA-05 Tax Invoice according to UN/CEFACT codelist 1001, D.16B for KSA.
 *  name="0211010": BR-KSA-06 starts with "02" Simplified Tax Invoice. Also explains other positions.
 * cac:AdditionalDocumentReference: ICV: KSA-16, BR-KSA-33 (Invoice Counter number)
 */

return /* XML */
    <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
    <ext:UBLExtensions>SET_UBL_EXTENSIONS_STRING
    </ext:UBLExtensions>
    <cbc:ProfileID>reporting:1.0</cbc:ProfileID>
    <cbc:ID>SET_INVOICE_SERIAL_NUMBER</cbc:ID>
    <cbc:UUID>SET_TERMINAL_UUID</cbc:UUID>
    <cbc:IssueDate>SET_ISSUE_DATE</cbc:IssueDate>
    <cbc:IssueTime>SET_ISSUE_TIME</cbc:IssueTime>
    <cbc:InvoiceTypeCode name="__invoice_type_code_attr_name">SET_INVOICE_TYPE</cbc:InvoiceTypeCode>
    <cbc:DocumentCurrencyCode>SAR</cbc:DocumentCurrencyCode>
    <cbc:TaxCurrencyCode>SAR</cbc:TaxCurrencyCode>SET_BILLING_REFERENCE
    <cac:AdditionalDocumentReference>
        <cbc:ID>ICV</cbc:ID>
        <cbc:UUID>SET_INVOICE_COUNTER_NUMBER</cbc:UUID>
    </cac:AdditionalDocumentReference>
    <cac:AdditionalDocumentReference>
        <cbc:ID>PIH</cbc:ID>
        <cac:Attachment>
            <cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain">SET_PREVIOUS_INVOICE_HASH</cbc:EmbeddedDocumentBinaryObject>
        </cac:Attachment>
    </cac:AdditionalDocumentReference>
    <cac:AdditionalDocumentReference>
        <cbc:ID>QR</cbc:ID>
        <cac:Attachment>
            <cbc:EmbeddedDocumentBinaryObject mimeCode="text/plain">SET_QR_CODE_DATA</cbc:EmbeddedDocumentBinaryObject>
        </cac:Attachment>
    </cac:AdditionalDocumentReference>
    <cac:Signature>
      <cbc:ID>urn:oasis:names:specification:ubl:signature:Invoice</cbc:ID>
      <cbc:SignatureMethod>urn:oasis:names:specification:ubl:dsig:enveloped:xades</cbc:SignatureMethod>
    </cac:Signature>
    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID="CRN">SET_COMMERCIAL_REGISTRATION_NUMBER</cbc:ID>
            </cac:PartyIdentification>
            <cac:PostalAddress>
                <cbc:StreetName>SET_STREET_NAME</cbc:StreetName>
                <cbc:BuildingNumber>SET_BUILDING_NUMBER</cbc:BuildingNumber>
                <cbc:PlotIdentification>SET_PLOT_IDENTIFICATION</cbc:PlotIdentification>
                <cbc:CitySubdivisionName>SET_CITY_SUBDIVISION</cbc:CitySubdivisionName>
                <cbc:CityName>SET_CITY</cbc:CityName>
                <cbc:PostalZone>SET_POSTAL_NUMBER</cbc:PostalZone>
                <cac:Country>
                    <cbc:IdentificationCode>SA</cbc:IdentificationCode>
                </cac:Country>
            </cac:PostalAddress>
            <cac:PartyTaxScheme>
                <cbc:CompanyID>SET_VAT_NUMBER</cbc:CompanyID>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:PartyTaxScheme>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>SET_VAT_NAME</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>
    <cac:AccountingCustomerParty>
		<cac:Party>
				<cac:PartyIdentification>
					<cbc:ID schemeID="CRN">__id</cbc:ID>
				</cac:PartyIdentification>
				<cac:PostalAddress>
					<cbc:StreetName>__street_name</cbc:StreetName>
					<cbc:BuildingNumber>__building_number</cbc:BuildingNumber>
					<cbc:PlotIdentification>__plotIdentification</cbc:PlotIdentification>
					<cbc:CitySubdivisionName>__city_subdivision_name</cbc:CitySubdivisionName>
					<cbc:CityName>__city_name</cbc:CityName>
					<cbc:PostalZone>__postal_zone</cbc:PostalZone>
					<cac:Country>
						<cbc:IdentificationCode>SA</cbc:IdentificationCode>
					</cac:Country>
				</cac:PostalAddress>
				<cac:PartyTaxScheme>
					<cbc:CompanyID>__company_id</cbc:CompanyID>
					<cac:TaxScheme>
						<cbc:ID>__tax_scheme_id</cbc:ID>
					</cac:TaxScheme>
				</cac:PartyTaxScheme>
				<cac:PartyLegalEntity>
					<cbc:RegistrationName>__registration_name</cbc:RegistrationName>
				</cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>
    <cac:Delivery>
		<cbc:ActualDeliveryDate>__ActualDeliveryDate</cbc:ActualDeliveryDate>
	</cac:Delivery>
    <cac:PaymentMeans>
        <cbc:PaymentMeansCode>10</cbc:PaymentMeansCode>
        <cbc:InstructionNote>CANCELLATION_OR_TERMINATION</cbc:InstructionNote>
    </cac:PaymentMeans>
    PARSE_LINE_ITEMS
</Invoice>
XML;