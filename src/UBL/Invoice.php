<?php

namespace ZATCA\UBL;

use DateTimeImmutable;
use ZATCA\Hacks;
use ZATCA\UBL\CommonAggregateComponents\Party;
use ZATCA\UBL\CommonAggregateComponents\Delivery;
use ZATCA\UBL\CommonAggregateComponents\LegalMonetaryTotal;
use ZATCA\UBL\Signing\Signature;
use ZATCA\UBL\Signing\SignedProperties;
use ZATCA\UBL\Signing\UBLExtensions;
use ZATCA\Hashing;
use ZATCA\Signing\Certificate;
use ZATCA\Signing\ECDSA;

class Invoice extends BaseComponent
{
    public const TYPES = [
        'invoice' => '388',
        'debit' => '383',
        'credit' => '381',
    ];

    public const PAYMENT_MEANS = [
        'cash' => '10',
        'credit' => '30',
        'bank_account' => '42',
        'bank_card' => '48',
    ];

    // these correspond to your `attr_reader` fields
    public ?string $signedHash = null;
    public mixed $signedHashBytes = null;
    public mixed $publicKeyBytes = null;
    public mixed $certificateSignature = null;
    public mixed $qualifyingProperties = null;

    // public for read/write like `attr_accessor`
    public ?Signature $signature = null;
    public ?string $qrCode = null;
    public ?string $unsignedXmlBase64 = null;

    public function __construct(
        private readonly string              $id,
        public readonly string               $uuid,
        public readonly string               $issueDate,
        public readonly string               $issueTime,
        public readonly string               $subtype,
        public readonly string               $type,
        private readonly string              $invoiceCounterValue,
        private readonly Party               $accountingSupplierParty,
        private readonly Party               $accountingCustomerParty,
        private readonly string              $paymentMeansCode,
        private readonly ?LegalMonetaryTotal $legalMonetaryTotal,
        private readonly array               $allowanceCharges = [],
        private readonly array               $taxTotals = [],
        private readonly array               $invoiceLines = [],
        private readonly string              $currencyCode = 'SAR',
        private readonly ?string             $note = null,
        private readonly ?string             $noteLanguageId = null,
        private readonly ?string             $lineCountNumeric = null,
        private readonly ?Delivery           $delivery = null,
        private readonly ?string             $previousInvoiceHash = null,
        private readonly bool                $addIdsToAllowanceCharges = true,
        private readonly ?string             $instructionNote = null,
        private readonly ?string             $billingReference = null,
        ?string                              $qrCode = null,
        ?Signature                           $signature = null
    ) {
        // set the two public props
        $this->qrCode = $qrCode;
        $this->signature = $signature;


        parent::__construct(
            elements: $this->elements(),
            attributes: $this->attributes(),
            value: '',
            name: $this->name(),
            index: null
        );
    }

    public function name(): string
    {
        return 'Invoice';
    }

    public function attributes(): array
    {
        return [
            'xmlns' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            'xmlns:cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            'xmlns:cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
            'xmlns:ext' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2',
        ];
    }

    public function elements(): array
    {
        $this->addSequentialIds();

        $elements = [];

        // -- UBL Extensions (signature block)
        if ($this->signature !== null) {
            $elements[] = new UBLExtensions(signature: $this->signature);
        }

        // -- Metadata
        $elements[] = new BaseComponent(attributes: [], value: 'reporting:1.0', name: 'cbc:ProfileID');
        $elements[] = new BaseComponent(attributes: [], value: $this->id, name: 'cbc:ID');
        $elements[] = new BaseComponent(attributes: [], value: $this->uuid, name: 'cbc:UUID');
        $elements[] = new BaseComponent(attributes: [], value: $this->issueDate, name: 'cbc:IssueDate');
        $elements[] = new BaseComponent(attributes: [], value: $this->issueTime, name: 'cbc:IssueTime');

        // -- Invoice Type
        $elements[] = new BaseComponent(
            attributes: ['name' => $this->subtype],
            value: $this->type,
            name: 'cbc:InvoiceTypeCode'
        );

        // -- Note
        if ($this->note !== null || $this->noteLanguageId !== null) {
            $attrs = [];
            if ($this->noteLanguageId !== null) {
                $attrs['languageID'] = $this->noteLanguageId;
            }
            $elements[] = new BaseComponent(
                attributes: $attrs,
                value: (string)$this->note,
                name: 'cbc:Note'
            );
        }

        // -- Currency
        $elements[] = new BaseComponent(value: $this->currencyCode, name: 'cbc:DocumentCurrencyCode');
        $elements[] = new BaseComponent(value: $this->currencyCode, name: 'cbc:TaxCurrencyCode');

        // -- BillingReference
        if ($this->billingReference !== null) {
            $elements[] = new BaseComponent(
                elements: [
                    new BaseComponent(
                        elements: [
                            new BaseComponent(value: $this->billingReference, name: 'cbc:ID')
                        ],
                        name: 'cac:InvoiceDocumentReference'
                    )
                ],
                name: 'cac:BillingReference'
            );
        }

        // -- LineCountNumeric
        if ($this->lineCountNumeric !== null) {
            $elements[] = new BaseComponent(
                value: $this->lineCountNumeric,
                name: 'cbc:LineCountNumeric'
            );
        }

        // -- AdditionalDocumentReference: ICV
        $elements[] = new BaseComponent(
            elements: [
                new BaseComponent(value: 'ICV', name: 'cbc:ID'),
                new BaseComponent(value: $this->invoiceCounterValue, name: 'cbc:UUID'),
            ],
            name: 'cac:AdditionalDocumentReference'
        );

        // -- PIH
        if ($this->previousInvoiceHash !== null) {
            $elements[] = new BaseComponent(
                elements: [
                    new BaseComponent(value: 'PIH', name: 'cbc:ID'),
                    new BaseComponent(
                        elements: [
                            new BaseComponent(
                                attributes: ['mimeCode' => 'text/plain'],
                                value: $this->previousInvoiceHash,
                                name: 'cbc:EmbeddedDocumentBinaryObject'
                            )
                        ],
                        name: 'cac:Attachment'
                    )
                ],
                name: 'cac:AdditionalDocumentReference'
            );
        }

        // -- QR
        if ($this->qrCode !== null) {
            $elements[] = new BaseComponent(
                elements: [
                    new BaseComponent(value: 'QR', name: 'cbc:ID'),
                    new BaseComponent(
                        elements: [
                            new BaseComponent(
                                attributes: ['mimeCode' => 'text/plain'],
                                value: $this->qrCode,
                                name: 'cbc:EmbeddedDocumentBinaryObject'
                            )
                        ],
                        name: 'cac:Attachment'
                    )
                ],
                name: 'cac:AdditionalDocumentReference'
            );
        }

        // -- Static signature placeholder
        if ($this->signature !== null) {
            $elements[] = new BaseComponent(
                elements: [
                    new BaseComponent(value: 'urn:oasis:names:specification:ubl:signature:Invoice', name: 'cbc:ID'),
                    new BaseComponent(value: 'urn:oasis:names:specification:ubl:dsig:enveloped:xades', name: 'cbc:SignatureMethod'),
                ],
                name: 'cac:Signature'
            );
        }

        // -- Parties
        $elements[] = new BaseComponent(
            elements: [$this->accountingSupplierParty],
            name: 'cac:AccountingSupplierParty'
        );
        $elements[] = new BaseComponent(
            elements: [$this->accountingCustomerParty],
            name: 'cac:AccountingCustomerParty'
        );

        // -- Delivery
        if ($this->delivery !== null) {
            $elements[] = $this->delivery;
        }

        // -- Payment Means
        $pmEls = [new BaseComponent(value: $this->paymentMeansCode, name: 'cbc:PaymentMeansCode')];
        if ($this->instructionNote !== null) {
            $pmEls[] = new BaseComponent(value: $this->instructionNote, name: 'cbc:InstructionNote');
        }
        $elements[] = new BaseComponent(elements: $pmEls, name: 'cac:PaymentMeans');

        // -- AllowanceCharges
        foreach ($this->allowanceCharges as $ac) {
            $elements[] = $ac;
        }

        // -- TaxTotals
        foreach ($this->taxTotals as $tt) {
            $elements[] = $tt;
        }

        // -- LegalMonetaryTotal
        $elements[] = $this->legalMonetaryTotal;

        // -- InvoiceLines
        foreach ($this->invoiceLines as $il) {
            $elements[] = $il;
        }

        return $elements;
    }

    protected function addSequentialIds(): void
    {
        if ($this->addIdsToAllowanceCharges) {
            foreach ($this->allowanceCharges as $i => $ac) {
                $ac->index = $i + 1;
            }
        }
        foreach ($this->invoiceLines as $i => $il) {
            $il->index = $i + 1;
        }
    }

    public function generateHash(): string
    {
        # We don't need to apply the hacks here because they only apply to the
        # QualifyingProperties block which is not present when generating the hash
        $xml = $this->generateUnsignedXml(
            canonicalized: true,
            applyInvoiceHacks: false,
            removeRootXmlTag: true
        );
        $this->unsignedXmlBase64 = base64_encode($xml);
        $hashes = Hashing::generateHashes($xml);
        return $hashes['base64'];
    }

    # When submitting to ZATCA, we need to submit the XML in Base64 format, and it
    # needs to be pretty-printed matching their indentation style.
    # The canonicalized option here is left only for debugging purposes.
    public function toBase64(bool $canonicalized = true): string
    {
        parent::__construct(
            elements: $this->elements(),
            attributes: $this->attributes(),
            value: '',
            name: $this->name(),
            index: null
        );

        $xml = $this->generateXml(
            canonicalized: $canonicalized,
            spaces: 4,
            applyInvoiceHacks: true,
            removeRootXmlTag: false,
        );

        $xml = str_replace(
            '<ds:Signature Id="signature" xmlns:ds="http://www.w3.org/2000/09/xmldsig#">',
            '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="signature">',
            $xml
        );

        return base64_encode($xml);
    }

    # HACK:
    # Override this method because dry-initializer isn't helping us by having
    # an after_initialize callback. We just need to set the qualifying properties
    # at any point before generating the XML.
    public function generateXml(
        bool $canonicalized = true,
        int  $spaces = 4,
        bool $applyInvoiceHacks = true,
        bool $removeRootXmlTag = false
    ): string {
        if ($this->signature) {
            $this->setQualifyingProperties(
                signingTime: $this->signature?->signingTime,
                certDigestValue: $this->signature?->certDigestValue,
                certIssuerName: $this->signature?->certIssuerName,
                certSerialNumber: $this->signature?->certSerialNumber
            );
        }

        return parent::generateXml(
            canonicalized: $canonicalized,
            spaces: $spaces,
            applyInvoiceHacks: $applyInvoiceHacks,
            removeRootXmlTag: $removeRootXmlTag
        );
    }

    public function generateUnsignedXml(
        bool $canonicalized = true,
        bool $applyInvoiceHacks = false,
        bool $removeRootXmlTag = false
    ): string {
        # HACK: Set signature and QR code to nil temporarily so they get removed
        # from the XML before generating the unsigned XML. An unsigned einvoice
        # should not have a signature or QR code, we additionally remove the qualifying
        # properties because it is a replacement that happens on the generated XML and
        # we only want that replacement on the version we submit to ZATCA.
        $origSig = $this->signature;
        $origQr = $this->qrCode;
        $origQual = $this->qualifyingProperties;

        $this->signature = null;
        $this->qrCode = null;
        $this->qualifyingProperties = null;

        $xml = $this->generateXml(
            canonicalized: $canonicalized,
            spaces: 4,
            applyInvoiceHacks: $applyInvoiceHacks,
            removeRootXmlTag: $removeRootXmlTag
        );

        $this->signature = $origSig;
        $this->qrCode = $origQr;
        $this->qualifyingProperties = $origQual;

        return $xml;
    }

    public function sign(
        string  $privateKey,
        string  $certificate,
        ?string $signingTime = null,
    ): void {
        $canonicalizedXml = $this->generateUnsignedXml(canonicalized: true, applyInvoiceHacks: false, removeRootXmlTag: false);

        $generateHashes = Hashing::generateHashes($canonicalizedXml);

        # Sign the invoice hash using the private key
        $signature = ECDSA::sign(
            content: $generateHashes['hexdigest'],
            privateKey: $privateKey,
        );

        $this->signedHash = $signature['base64'];
        $this->signedHashBytes = $signature['bytes'];

        # Parse and hash the certificate
        $cert = Certificate::readCertificate($certificate);
        $this->publicKeyBytes = $cert->publicKeyBytes;

        # Current Version
        $this->certificateSignature = $cert->signature;

        # ZATCA requires a different set of attributes when hashing the SignedProperties
        # attributes and does not want those attributes present in the actual XML.
        # So we'll have two sets of signed properties for this purpose, one just
        # to generate a hash out of, and one to actually include in the XML.
        # See: https://zatca1.discourse.group/t/what-do-signed-properties-look-like-when-hashing/717
        #
        # The other SignedProperties that's in the XML is generated when we construct
        # the Signature element below

        // build the “for‐hashing” block
        $signedPropertiesForHashing = new SignedProperties(
            signingTime: $signingTime,
            certDigestValue: $cert->hash,
            certIssuerName: $cert->issuerName,
            certSerialNumber: $cert->serialNumber
        );

        $this->setQualifyingProperties(
            signingTime: $signingTime,
            certDigestValue: $cert->hash,
            certIssuerName: $cert->issuerName,
            certSerialNumber: $cert->serialNumber
        );

        # ZATCA uses very specific whitespace also for the version of this block
        # that we need to submit to their servers, so we will keep a copy of the XML
        # as it should be spaced, and then after building the XML we will replace
        # the QualifyingProperties block with this one.
        #
        # See: https://zatca1.discourse.group/t/what-do-signed-properties-look-like-when-hashing/717
        #
        # If their server is ever updated to format the block before hashing it on their
        # end, we can safely remove this behavior.
        $this->qualifyingProperties = Hacks::zatcaIndentedQualifyingProperties(
            signingTime: $signingTime,
            certDigestValue: $cert->hash,
            certIssuerName: $cert->issuerName,
            certSerialNumber: $cert->serialNumber
        );

        $signedPropsHash = $signedPropertiesForHashing->generateHash();

        # Create the signature element using the certificate, invoice hash, and signed
        # properties hash
        $this->signature = new Signature(
            invoiceDigest: $generateHashes['base64'],
            signedPropertiesHash: $signedPropsHash,

            # Current Version
            signatureValue: $this->signedHash,
            certificate: $cert->certContentWithoutHeaders,
            signingTime: $signingTime,
            certDigestValue: $cert->hash,
            certIssuerName: $cert->issuerName,
            certSerialNumber: $cert->serialNumber
        );
    }

    private function setQualifyingProperties(
        string $signingTime,
        string            $certDigestValue,
        string            $certIssuerName,
        string            $certSerialNumber
    ): void {
        $this->qualifyingProperties = Hacks::zatcaIndentedQualifyingProperties(
            signingTime: $signingTime,
            certDigestValue: $certDigestValue,
            certIssuerName: $certIssuerName,
            certSerialNumber: $certSerialNumber
        );
    }
}
