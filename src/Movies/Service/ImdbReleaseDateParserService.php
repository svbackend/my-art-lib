<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Countries\Entity\Country;
use App\Movies\Entity\Movie;
use Symfony\Component\DomCrawler\Crawler;

class ImdbReleaseDateParserService
{
    private const IMDB_RELEASE_DATES_URL = 'https://imdb.com/title/{id}/releaseinfo?ref_=tt_dt_dt';

    public function getReleaseDates(Movie $movie): array
    {
        if ($movie->getImdbId() === null) {
            return [];
        }

        $html = <<<'HTML'
<!DOCTYPE html>
<html>
    <body>
        <p class="message">Hello World!</p>
        <p>Hello Crawler!</p>
        <table id="release_dates" class="subpage_data spFirst">
        <tbody><tr class="odd">
            <td><a href="/calendar/?region=gb&amp;ref_=ttrel_rel_1">UK</a></td>
            <td class="release_date">23 October 2018</td>
            <td> (London)
 (premiere)</td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=gb&amp;ref_=ttrel_rel_2">UK</a></td>
            <td class="release_date">24 October 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=ie&amp;ref_=ttrel_rel_3">Ireland</a></td>
            <td class="release_date">24 October 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=de&amp;ref_=ttrel_rel_4">Germany</a></td>
            <td class="release_date">31 October 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=es&amp;ref_=ttrel_rel_5">Spain</a></td>
            <td class="release_date">31 October 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=fr&amp;ref_=ttrel_rel_6">France</a></td>
            <td class="release_date">31 October 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=id&amp;ref_=ttrel_rel_7">Indonesia</a></td>
            <td class="release_date">31 October 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=kr&amp;ref_=ttrel_rel_8">South Korea</a></td>
            <td class="release_date">31 October 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=ph&amp;ref_=ttrel_rel_9">Philippines</a></td>
            <td class="release_date">31 October 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=pt&amp;ref_=ttrel_rel_10">Portugal</a></td>
            <td class="release_date">31 October 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=se&amp;ref_=ttrel_rel_11">Sweden</a></td>
            <td class="release_date">31 October 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=ar&amp;ref_=ttrel_rel_12">Argentina</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=au&amp;ref_=ttrel_rel_13">Australia</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=bd&amp;ref_=ttrel_rel_14">Bangladesh</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=br&amp;ref_=ttrel_rel_15">Brazil</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=co&amp;ref_=ttrel_rel_16">Colombia</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=cz&amp;ref_=ttrel_rel_17">Czech Republic</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=dk&amp;ref_=ttrel_rel_18">Denmark</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=gr&amp;ref_=ttrel_rel_19">Greece</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=hk&amp;ref_=ttrel_rel_20">Hong Kong</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=hu&amp;ref_=ttrel_rel_21">Hungary</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=il&amp;ref_=ttrel_rel_22">Israel</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=nl&amp;ref_=ttrel_rel_23">Netherlands</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=nz&amp;ref_=ttrel_rel_24">New Zealand</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=pe&amp;ref_=ttrel_rel_25">Peru</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=ru&amp;ref_=ttrel_rel_26">Russia</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=sg&amp;ref_=ttrel_rel_27">Singapore</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=sk&amp;ref_=ttrel_rel_28">Slovakia</a></td>
            <td class="release_date">1 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=bg&amp;ref_=ttrel_rel_29">Bulgaria</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=ca&amp;ref_=ttrel_rel_30">Canada</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=ee&amp;ref_=ttrel_rel_31">Estonia</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=fi&amp;ref_=ttrel_rel_32">Finland</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=lt&amp;ref_=ttrel_rel_33">Lithuania</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=mx&amp;ref_=ttrel_rel_34">Mexico</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=no&amp;ref_=ttrel_rel_35">Norway</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=np&amp;ref_=ttrel_rel_36">Nepal</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=pl&amp;ref_=ttrel_rel_37">Poland</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=ro&amp;ref_=ttrel_rel_38">Romania</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=tr&amp;ref_=ttrel_rel_39">Turkey</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=us&amp;ref_=ttrel_rel_40">USA</a></td>
            <td class="release_date">2 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=jp&amp;ref_=ttrel_rel_41">Japan</a></td>
            <td class="release_date">9 November 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=it&amp;ref_=ttrel_rel_42">Italy</a></td>
            <td class="release_date">29 November 2018</td>
            <td></td>
        </tr>
        <tr class="odd">
            <td><a href="/calendar/?region=za&amp;ref_=ttrel_rel_43">South Africa</a></td>
            <td class="release_date">14 December 2018</td>
            <td></td>
        </tr>
        <tr class="even">
            <td><a href="/calendar/?region=es&amp;ref_=ttrel_rel_44">Spain</a></td>
            <td class="release_date">28 December 2018</td>
            <td></td>
        </tr>
    </tbody></table>
    </body>
</html>
HTML;


        $crawler = new Crawler($html, self::IMDB_RELEASE_DATES_URL);

        $tds = $crawler->filterXPath('//*[@id="release_dates"]//td')->getIterator();

        $result = [];
        $country = '';
        foreach ($tds as $td) {
            if ($td->getAttribute('class') === 'release_date') {
                $result[$country] = $td->textContent;
                continue;
            }

            if ($td->hasChildNodes() && $td->getElementsByTagName('a')->item(0) !== null) {
                $country = $td->getElementsByTagName('a')->item(0)->textContent;
                continue;
            }
        }

        // todo map IMDB country and date to our format

        return [
            'UKR' => new \DateTime(),
        ];
    }
}
