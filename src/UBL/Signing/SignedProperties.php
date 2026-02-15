<?php

namespace ZATCA\UBL\Signing;

use DOMDocument;
use DOMXPath;
use ZATCA\Hacks;
use ZATCA\Hashing;
use ZATCA\UBL\BaseComponent;
use DateTimeImmutable;

class SignedProperties extends BaseComponent
{
    private readonly string $signingTime;
    private readonly string $certDigestValue;
    private readonly string $certIssuerName;
    private readonly string $certSerialNumber;

    public function __construct(
        string $signingTime,
        string $certDigestValue,
        string $certIssuerName,
        string $certSerialNumber
    ) {
        $this->signingTime = $signingTime;
        $this->certDigestValue = $certDigestValue;
        $this->certIssuerName = $certIssuerName;
        $this->certSerialNumber = $certSerialNumber;

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
        return 'xades:SignedProperties';
    }

    public function attributes(): array
    {
        return ['Id' => 'xadesSignedProperties'];
    }

    public function elements(): array
    {
        return [
            new SignedSignatureProperties(
                signingTime: $this->signingTime,
                certDigestValue: $this->certDigestValue,
                certIssuerName: $this->certIssuerName,
                certSerialNumber: $this->certSerialNumber
            )
        ];
    }

    /**
     * Produce the hexdigest_base64 of the *exact same* whitespace‐spaced
     * SignedProperties block that we’ll inject into the final invoice.
     */
    public function generateHash(): string
    {
        $raw = $this->zatcaWhitespacedXmlForHashing();
        $binaryDigest = hash('sha256', $raw);
        return base64_encode($binaryDigest);
    }

    # rubocop:disable Layout/HeredocIndentation
    # rubocop:disable Layout/ClosingHeredocIndentation
    #
    # We need this version of the XML with this exact whitespace because ZATCA
    # did not opt for canonicalization before hashing (unlike the invoice hash),
    # and also because they have XML attributes that are only added the moment you
    # generate the hash but are expected to be removed in all other instances.
    #
    # We will use this version of the XML to generate the hash only, and expect
    # the output of `build_xml` to generate the non-signing version of the XML.
    # See:
    # - https://zatca1.discourse.group/t/what-do-signed-properties-look-like-when-hashing
    # - https://web.archive.org/web/20230925182417/https://zatca1.discourse.group/t/what-do-signed-properties-look-like-when-hashing/717
    private function zatcaWhitespacedXmlForHashing(): string
    {
        $xml = <<<XML
<xades:SignedProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="xadesSignedProperties">
                                    <xades:SignedSignatureProperties>
                                        <xades:SigningTime>{$this->signingTime}</xades:SigningTime>
                                        <xades:SigningCertificate>
                                            <xades:Cert>
                                                <xades:CertDigest>
                                                    <ds:DigestMethod xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                                                    <ds:DigestValue xmlns:ds="http://www.w3.org/2000/09/xmldsig#">{$this->certDigestValue}</ds:DigestValue>
                                                </xades:CertDigest>
                                                <xades:IssuerSerial>
                                                    <ds:X509IssuerName xmlns:ds="http://www.w3.org/2000/09/xmldsig#">{$this->certIssuerName}</ds:X509IssuerName>
                                                    <ds:X509SerialNumber xmlns:ds="http://www.w3.org/2000/09/xmldsig#">{$this->certSerialNumber}</ds:X509SerialNumber>
                                                </xades:IssuerSerial>
                                            </xades:Cert>
                                        </xades:SigningCertificate>
                                    </xades:SignedSignatureProperties>
                                </xades:SignedProperties>
XML;

        // Remove only the final newline:
        return rtrim($xml, "\n");
    }
}
