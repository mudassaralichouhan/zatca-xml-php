<?php

namespace ZATCA\UBL;

use DOMDocument;
use ZATCA\Hacks;

class Builder
{
    private BaseComponent $element;

    public function __construct(BaseComponent $element)
    {
        $this->element = $element;
    }

    public function build(
        bool $canonicalized = false,
        int $spaces = 4,
        bool $applyInvoiceHacks = false,
        bool $removeRootXmlTag = false,
    ): string {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $this->element->buildXml($doc, $doc);

        if ($canonicalized) {
            $xml = $this->canonicalizedXml($doc);
        } else {
            $xml = $this->uncanonicalizedXml($doc, $spaces);
        }

        //        // Turn any <tag></tag> (with optional whitespace) into <tag/>
        //        $regex = '/<([^\s>\/]+)([^>]*)>\s*<\/\1>/u';
        //        $xml = preg_replace(
        //            $regex,
        //            '<$1$2/>',
        //            $xml
        //        );
        //        do {
        //            $new = preg_replace($regex, '<$1$2/>', $xml);
        //            if ($new === $xml) break;
        //            $xml = $new;
        //        } while (true);

        if ($applyInvoiceHacks) {
            $xml = $this->applyHacksToInvoice($this->element, $xml);
        }

        return $xml;
    }

    # ZATCA sadly requires very specific and unconventional indentation in the XML
    # when it is pretty (uncanonicalized), the only way we can accomplish this is
    # to find and replace blocks manually.
    private function applyHacksToInvoice(BaseComponent $element, string $xml): string
    {
        if (!($element instanceof Invoice)) {
            return $xml;
        }

        return $this->applyQualifyingPropertiesHacks($element, $xml);
    }

    private function applyQualifyingPropertiesHacks(Invoice $invoice, string $xml): string
    {
        if (empty($invoice->qualifyingProperties)) {
            return $xml;
        }
        $regex = Hacks::qualifyingPropertiesRegex();
        return preg_replace($regex, $invoice->qualifyingProperties, $xml);
    }

    private function canonicalizedXml(DOMDocument $doc): string
    {
        // Note: PHP's C14N is 1.0 by default; adjust if 1.1 support is needed
        return $doc->C14N(true, false);
    }

    # This function does not produce canonicalization matching C14N 1.1, it applies
    # C14N 1.1 then manually adds back the whitespace in the format that ZATCA
    # expects.
    private function uncanonicalizedXml(\DOMDocument $doc, int $spaces): string
    {
        # TODO: In case ZATCA ever asks us to use their whitespace format again.
        # In some meetings they say we have to use it, in some meetings they say
        # we don't. The simpler approach is that we don't use it.
        #
        # ZATCA's docs specifically state we must use C14N 1.1 canonicalization.
        # xml = uncanonicalized_xml(builder: builder, spaces: 4)
        # xml_doc = Nokogiri::XML(xml)

        # canonical_xml = xml_doc.canonicalize(Nokogiri::XML::XML_C14N_1_1)

        # canonical_xml

        $doc->preserveWhiteSpace = false;
        $doc->formatOutput       = true;
        $xml = $doc->saveXML();

        // Turn any <tag></tag> (with optional whitespace) into <tag/>
        //        $xml = preg_replace(
        //            '/<(\w+)([^>]*)>\s*<\/\1>/u',
        //            '<$1$2/>',
        //            $xml
        //        );

        $xml = preg_replace_callback(
            '/^( +)/m',
            function (array $m) use ($spaces) {
                $orig = strlen($m[1]);         // e.g. 4 spaces
                $levels = (int)($orig / 2);    // how many indent-levels
                return str_repeat(' ', $levels * $spaces);
            },
            $xml
        );

        return $xml;
    }
}
