<?php

declare(strict_types=1);

namespace App\Tests\Village\Domain;

use App\Village\Domain\Stage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StageTest extends TestCase
{
    /**
     * Cortes de nivel placeholder (game-design.md, sección 4.4 — pendiente
     * de balance real): 1-9 Aldea, 10-24 Villa, 25-49 Ciudad, 50+ Reino.
     */
    public static function levelsAndExpectedStages(): iterable
    {
        yield 'lowest possible level' => [1, Stage::Aldea];
        yield 'last level of Aldea' => [9, Stage::Aldea];
        yield 'first level of Villa' => [10, Stage::Villa];
        yield 'last level of Villa' => [24, Stage::Villa];
        yield 'first level of Ciudad' => [25, Stage::Ciudad];
        yield 'last level of Ciudad' => [49, Stage::Ciudad];
        yield 'first level of Reino' => [50, Stage::Reino];
        yield 'a much higher level of Reino' => [500, Stage::Reino];
    }

    #[DataProvider('levelsAndExpectedStages')]
    public function test_from_main_building_level_maps_to_the_expected_stage(int $level, Stage $expected): void
    {
        self::assertSame($expected, Stage::fromMainBuildingLevel($level));
    }

    public static function stagesAndExpectedMainBuildingNames(): iterable
    {
        yield [Stage::Aldea, 'Choza del Fundador'];
        yield [Stage::Villa, 'Salón del Clan'];
        yield [Stage::Ciudad, 'Casa Solariega'];
        yield [Stage::Reino, 'Trono de la Dinastía'];
    }

    #[DataProvider('stagesAndExpectedMainBuildingNames')]
    public function test_main_building_name_matches_game_design(Stage $stage, string $expectedName): void
    {
        self::assertSame($expectedName, $stage->mainBuildingName());
    }
}
