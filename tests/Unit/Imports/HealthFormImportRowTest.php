<?php

namespace Tests\Unit\Imports;

use App\Imports\HealthFormImportRow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Eine Zeile der Teilnehmerliste (Vorlage public/files/vorlage.xlsx), ohne Laravel und Datenbank.
 */
class HealthFormImportRowTest extends TestCase
{
    /**
     * Erlaubt: Excel-Datum, TT.MM.JJJJ, T.M.JJJJ, TT/MM/JJJJ, JJJJ-MM-TT.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function validBirthdays(): array
    {
        return [
            'Excel-Datum (Zahl)' => [41046, '2012-05-17'],
            'Excel-Datum als Text' => ['41046', '2012-05-17'],
            'TT.MM.JJJJ' => ['03.02.2011', '2011-02-03'],
            'T.M.JJJJ' => ['3.2.2011', '2011-02-03'],
            'TT/MM/JJJJ' => ['17/05/2012', '2012-05-17'],
            'JJJJ-MM-TT' => ['2011-02-03', '2011-02-03'],
            'mit Leerzeichen' => [' 17.05.2012 ', '2012-05-17'],
            'Schaltjahr' => ['29.02.2012', '2012-02-29'],
        ];
    }

    #[DataProvider('validBirthdays')]
    public function test_valid_birthday_is_converted(mixed $value, string $expected): void
    {
        $this->assertSame($expected, HealthFormImportRow::parseBirthday($value));
    }

    /**
     * Alles andere wird nicht geraten (früher: heutiges Datum, Überlauf in den nächsten Monat, Absturz).
     *
     * @return array<string, array{mixed}>
     */
    public static function invalidBirthdays(): array
    {
        return [
            'zweistelliges Jahr' => ['3.2.11'],
            '31. Februar' => ['31.02.2012'],
            'kein Schaltjahr' => ['29.02.2011'],
            'US-Format' => ['05/17/2012'],
            'Monat 13' => ['01.13.2012'],
            'Text' => ['abc'],
            'Zukunft' => ['01.01.2999'],
            'vor 1900' => ['01.01.1899'],
            'Excel-Null' => [0],
            'negative Zahl' => [-5],
        ];
    }

    #[DataProvider('invalidBirthdays')]
    public function test_invalid_birthday_is_rejected(mixed $value): void
    {
        $this->assertNull(HealthFormImportRow::parseBirthday($value));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function emptyBirthdays(): array
    {
        return ['null' => [null], 'leer' => [''], 'Leerzeichen' => ['   ']];
    }

    #[DataProvider('emptyBirthdays')]
    public function test_empty_birthday_is_empty_not_invalid(mixed $value): void
    {
        $this->assertTrue(HealthFormImportRow::isEmpty($value));
        $this->assertNull(HealthFormImportRow::parseBirthday($value));
    }

    public function test_attributes_are_trimmed_and_nickname_falls_back_to_first_name(): void
    {
        $attributes = HealthFormImportRow::attributes([
            'abteilung' => ' CZH ',
            'nachname' => ' Muster ',
            'vorname' => ' Anna ',
            'ceviname' => '',
            'strasse' => 'Weg 1 ',
            'plz' => 8000,
            'ort' => ' Zürich',
            'geburtstag' => '17.05.2012',
            'ahv_nr' => ' 756.1234.5678.97 ',
        ]);

        $this->assertSame([
            'first_name' => 'Anna',
            'last_name' => 'Muster',
            'nickname' => 'Anna',
            'street' => 'Weg 1',
            'zip_code' => '8000',
            'city' => 'Zürich',
            'birthday' => '2012-05-17',
            'ahv' => '756.1234.5678.97',
            'group_text' => 'CZH',
        ], $attributes);
    }

    public function test_missing_columns_do_not_break_the_row(): void
    {
        $attributes = HealthFormImportRow::attributes(['vorname' => 'Ben', 'ceviname' => 'Bär']);

        $this->assertSame('Bär', $attributes['nickname']);
        $this->assertNull($attributes['birthday']);
        $this->assertSame('', $attributes['ahv']);
    }
}
