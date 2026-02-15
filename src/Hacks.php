<?php

namespace ZATCA;

use DateTimeImmutable;

class Hacks
{
    /**
     * Return the exact “QualifyingProperties” block with ZATCA’s required indentation.
     * Note: 28 spaces on the first line, then +4 each level.
     */
    public static function zatcaIndentedQualifyingProperties(
        string $signingTime,
        string $certDigestValue,
        string $certIssuerName,
        string $certSerialNumber
    ): string {
        return rtrim(
            <<<XML
                                    <xades:QualifyingProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Target="signature">
                                        <xades:SignedProperties Id="xadesSignedProperties">
                                            <xades:SignedSignatureProperties>
                                                <xades:SigningTime>{$signingTime}</xades:SigningTime>
                                                <xades:SigningCertificate>
                                                    <xades:Cert>
                                                        <xades:CertDigest>
                                                            <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                                                            <ds:DigestValue>{$certDigestValue}</ds:DigestValue>
                                                        </xades:CertDigest>
                                                        <xades:IssuerSerial>
                                                            <ds:X509IssuerName>{$certIssuerName}</ds:X509IssuerName>
                                                            <ds:X509SerialNumber>{$certSerialNumber}</ds:X509SerialNumber>
                                                        </xades:IssuerSerial>
                                                    </xades:Cert>
                                                </xades:SigningCertificate>
                                            </xades:SignedSignatureProperties>
                                        </xades:SignedProperties>
                                    </xades:QualifyingProperties>
        XML
        );
    }

    /**
     * Regex to find (and replace) the entire QualifyingProperties block.
     */
    public static function qualifyingPropertiesRegex(): string
    {
        // DOTALL flag so “.*?” matches newlines as well
        return '/<xades:QualifyingProperties\b.*?<\/xades:QualifyingProperties>/s';
    }
}
