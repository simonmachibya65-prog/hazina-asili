<?php
/**
 * Unit Tests — Organism Structure Feature
 * Run: php tests/OrganismTest.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Organism.php';

class OrganismTest {
    private Organism $model;
    private int $passed = 0;
    private int $failed = 0;
    private int $createdId = 0;

    public function __construct() {
        $this->model = new Organism();
    }

    // ── Test Runner ──────────────────────────────────────────────
    public function run(): void {
        echo "╔══════════════════════════════════════════════════╗\n";
        echo "║   ORGANISM STRUCTURE — UNIT TESTS               ║\n";
        echo "╚══════════════════════════════════════════════════╝\n\n";

        // Model CRUD Tests
        $this->section("MODEL: CREATE");
        $this->testCreateOrganism();
        $this->testCreateDuplicatePrevention();
        $this->testCreateWithAllFields();

        $this->section("MODEL: READ");
        $this->testFindById();
        $this->testFindByIdNotFound();
        $this->testGetAll();
        $this->testGetAllWithPagination();
        $this->testCountAll();

        $this->section("MODEL: SEARCH");
        $this->testSearchByKingdom();
        $this->testSearchByFamily();
        $this->testSearchByOrder();
        $this->testSearchByGenus();
        $this->testSearchByHabitat();
        $this->testSearchByScientificName();
        $this->testSearchNoResults();

        $this->section("MODEL: UPDATE");
        $this->testUpdateOrganism();
        $this->testUpdateImage();

        $this->section("MODEL: RELATIONSHIPS");
        $this->testGetCompounds();
        $this->testGetCompoundsEmpty();
        $this->testGetByKingdom();
        $this->testGetDistinctKingdoms();
        $this->testFindByKingdom();

        $this->section("MODEL: DUPLICATE DETECTION");
        $this->testFindByScientificName();
        $this->testFindByScientificNameExclude();
        $this->testFindByScientificNameNotFound();

        $this->section("MODEL: DELETE");
        $this->testDeleteOrganism();
        $this->testDeleteVerify();

        $this->section("MODEL: HELPERS");
        $this->testGetAllSimple();

        $this->section("VALIDATION LOGIC");
        $this->testCellTypeValues();
        $this->testNullableFields();

        // Results
        echo "\n╔══════════════════════════════════════════════════╗\n";
        $total = $this->passed + $this->failed;
        if ($this->failed === 0) {
            echo "║  ✅ ALL {$total} TESTS PASSED                       ║\n";
        } else {
            echo "║  ❌ {$this->passed} PASSED, {$this->failed} FAILED (total: {$total})         ║\n";
        }
        echo "╚══════════════════════════════════════════════════╝\n";
    }

    // ── Assertions ───────────────────────────────────────────────
    private function assert(bool $condition, string $message): void {
        if ($condition) {
            $this->passed++;
            echo "  ✅ {$message}\n";
        } else {
            $this->failed++;
            echo "  ❌ {$message}\n";
        }
    }

    private function assertEqual($expected, $actual, string $message): void {
        $pass = $expected === $actual;
        if ($pass) {
            $this->passed++;
            echo "  ✅ {$message}\n";
        } else {
            $this->failed++;
            echo "  ❌ {$message}\n";
            echo "     Expected: " . var_export($expected, true) . "\n";
            echo "     Actual:   " . var_export($actual, true) . "\n";
        }
    }

    private function assertGreaterThan(int $min, int $actual, string $message): void {
        $this->assert($actual > $min, "{$message} (got: {$actual})");
    }

    private function assertNull($value, string $message): void {
        $this->assert($value === null || $value === false, $message);
    }

    private function assertNotNull($value, string $message): void {
        $this->assert($value !== null && $value !== false, $message);
    }

    private function section(string $title): void {
        echo "\n  ── {$title} ─────────────────────────────────\n";
    }

    // ── CREATE Tests ─────────────────────────────────────────────
    private function testCreateOrganism(): void {
        $data = [
            'kingdom'         => 'Plantae',
            'phylum'          => 'Magnoliophyta',
            'class'           => 'Magnoliopsida',
            'order_name'      => 'Rosales',
            'family'          => 'Rosaceae',
            'genus'           => 'Rosa',
            'species'         => 'canina',
            'scientific_name' => 'Rosa canina_test_' . time(),
            'cell_type'       => 'eukaryotic',
            'habitat'         => 'Europe, northwest Africa, western Asia',
            'description'     => 'Dog rose, a variable climbing wild rose species.',
            'structure_image' => null,
        ];
        $this->createdId = $this->model->create($data);
        $this->assertGreaterThan(0, $this->createdId, "create() returns valid ID");
    }

    private function testCreateDuplicatePrevention(): void {
        $existing = $this->model->findByScientificName('Camellia sinensis');
        $this->assertNotNull($existing, "findByScientificName detects existing organism");
    }

    private function testCreateWithAllFields(): void {
        $data = [
            'kingdom'         => 'Animalia',
            'phylum'          => 'Chordata',
            'class'           => 'Mammalia',
            'order_name'      => 'Primates',
            'family'          => 'Hominidae',
            'genus'           => 'Homo',
            'species'         => 'sapiens',
            'scientific_name' => 'Homo sapiens_test_' . time(),
            'cell_type'       => 'eukaryotic',
            'habitat'         => 'Worldwide',
            'description'     => 'Test organism with all fields populated.',
            'structure_image' => 'test_image.jpg',
        ];
        $id = $this->model->create($data);
        $org = $this->model->findById($id);
        $this->assertEqual('Primates', $org['order_name'], "All fields saved: order_name");
        $this->assertEqual('Hominidae', $org['family'], "All fields saved: family");
        $this->assertEqual('test_image.jpg', $org['structure_image'], "All fields saved: structure_image");
        // Cleanup
        $this->model->delete($id);
    }

    // ── READ Tests ───────────────────────────────────────────────
    private function testFindById(): void {
        $org = $this->model->findById($this->createdId);
        $this->assertNotNull($org, "findById() returns organism");
        $this->assertEqual('Rosaceae', $org['family'], "findById() has correct family");
        $this->assertEqual('eukaryotic', $org['cell_type'], "findById() has correct cell_type");
    }

    private function testFindByIdNotFound(): void {
        $org = $this->model->findById(99999);
        $this->assertNull($org, "findById(99999) returns null for non-existent");
    }

    private function testGetAll(): void {
        $all = $this->model->getAll(0, 100);
        $this->assertGreaterThan(0, count($all), "getAll() returns organisms");
        $this->assert(isset($all[0]['order_name']), "getAll() includes order_name field");
        $this->assert(isset($all[0]['family']), "getAll() includes family field");
        $this->assert(isset($all[0]['cell_type']), "getAll() includes cell_type field");
    }

    private function testGetAllWithPagination(): void {
        $page1 = $this->model->getAll(0, 3);
        $page2 = $this->model->getAll(3, 3);
        $this->assertEqual(3, count($page1), "Pagination: page 1 has 3 items");
        $this->assertGreaterThan(0, count($page2), "Pagination: page 2 has items");
        $this->assert($page1[0]['id'] !== $page2[0]['id'], "Pagination: pages have different data");
    }

    private function testCountAll(): void {
        $count = $this->model->countAll();
        $this->assertGreaterThan(5, $count, "countAll() returns > 5");
    }

    // ── SEARCH Tests ─────────────────────────────────────────────
    private function testSearchByKingdom(): void {
        $count = $this->model->countAll('Plantae');
        $this->assertGreaterThan(0, $count, "Search by kingdom 'Plantae' finds results");
    }

    private function testSearchByFamily(): void {
        $count = $this->model->countAll('Theaceae');
        $this->assertGreaterThan(0, $count, "Search by family 'Theaceae' finds results");
    }

    private function testSearchByOrder(): void {
        $count = $this->model->countAll('Zingiberales');
        $this->assertGreaterThan(0, $count, "Search by order 'Zingiberales' finds results");
    }

    private function testSearchByGenus(): void {
        $count = $this->model->countAll('Camellia');
        $this->assertGreaterThan(0, $count, "Search by genus 'Camellia' finds results");
    }

    private function testSearchByHabitat(): void {
        $count = $this->model->countAll('Tropical');
        $this->assertGreaterThan(0, $count, "Search by habitat 'Tropical' finds results");
    }

    private function testSearchByScientificName(): void {
        $count = $this->model->countAll('sinensis');
        $this->assertGreaterThan(0, $count, "Search by scientific name 'sinensis' finds results");
    }

    private function testSearchNoResults(): void {
        $count = $this->model->countAll('xyznonexistent12345');
        $this->assertEqual(0, $count, "Search for non-existent returns 0");
    }

    // ── UPDATE Tests ─────────────────────────────────────────────
    private function testUpdateOrganism(): void {
        $data = [
            'kingdom'         => 'Plantae',
            'phylum'          => 'Magnoliophyta',
            'class'           => 'Magnoliopsida',
            'order_name'      => 'Rosales',
            'family'          => 'Rosaceae',
            'genus'           => 'Rosa',
            'species'         => 'canina',
            'scientific_name' => 'Rosa canina UPDATED_' . time(),
            'cell_type'       => 'eukaryotic',
            'habitat'         => 'Updated habitat: Europe and Asia',
            'description'     => 'Updated description.',
            'structure_image' => 'updated_image.png',
        ];
        $result = $this->model->update($this->createdId, $data);
        $this->assert($result === true, "update() returns true");

        $org = $this->model->findById($this->createdId);
        $this->assertEqual('Updated habitat: Europe and Asia', $org['habitat'], "update() persists habitat change");
        $this->assertEqual('updated_image.png', $org['structure_image'], "update() persists image change");
    }

    private function testUpdateImage(): void {
        $result = $this->model->updateImage($this->createdId, 'new_structure.jpg');
        $this->assert($result === true, "updateImage() returns true");

        $org = $this->model->findById($this->createdId);
        $this->assertEqual('new_structure.jpg', $org['structure_image'], "updateImage() persists correctly");

        // Test remove image
        $this->model->updateImage($this->createdId, null);
        $org = $this->model->findById($this->createdId);
        $this->assertNull($org['structure_image'], "updateImage(null) removes image");
    }

    // ── RELATIONSHIP Tests ───────────────────────────────────────
    private function testGetCompounds(): void {
        // Organism ID 1 (Camellia sinensis) should have compounds
        $compounds = $this->model->getCompounds(1);
        $this->assertGreaterThan(0, count($compounds), "getCompounds(1) returns linked compounds");
        $this->assert(isset($compounds[0]['name']), "Compound has 'name' field");
        $this->assert(isset($compounds[0]['formula']), "Compound has 'formula' field");
    }

    private function testGetCompoundsEmpty(): void {
        $compounds = $this->model->getCompounds($this->createdId);
        $this->assertEqual(0, count($compounds), "getCompounds() returns empty for new organism");
    }

    private function testGetByKingdom(): void {
        $byKingdom = $this->model->getByKingdom();
        $this->assertGreaterThan(1, count($byKingdom), "getByKingdom() returns multiple groups");
        $this->assert(isset($byKingdom[0]['kingdom']), "getByKingdom() has 'kingdom' key");
        $this->assert(isset($byKingdom[0]['cnt']), "getByKingdom() has 'cnt' key");
    }

    private function testGetDistinctKingdoms(): void {
        $kingdoms = $this->model->getDistinctKingdoms();
        $this->assertGreaterThan(1, count($kingdoms), "getDistinctKingdoms() returns multiple");
        $this->assert(in_array('Plantae', $kingdoms), "getDistinctKingdoms() includes 'Plantae'");
    }

    private function testFindByKingdom(): void {
        $plants = $this->model->findByKingdom('Plantae');
        $this->assertGreaterThan(0, count($plants), "findByKingdom('Plantae') returns organisms");
    }

    // ── DUPLICATE DETECTION Tests ────────────────────────────────
    private function testFindByScientificName(): void {
        $found = $this->model->findByScientificName('Camellia sinensis');
        $this->assertNotNull($found, "findByScientificName() finds existing");
        $this->assertEqual('Camellia sinensis', $found['scientific_name'], "Returns correct organism");
    }

    private function testFindByScientificNameExclude(): void {
        $found = $this->model->findByScientificName('Camellia sinensis', 1);
        $this->assertNull($found, "findByScientificName() with excludeId=self returns null");
    }

    private function testFindByScientificNameNotFound(): void {
        $found = $this->model->findByScientificName('Nonexistent organism xyz');
        $this->assertNull($found, "findByScientificName() returns null for non-existent");
    }

    // ── DELETE Tests ─────────────────────────────────────────────
    private function testDeleteOrganism(): void {
        $result = $this->model->delete($this->createdId);
        $this->assert($result === true, "delete() returns true");
    }

    private function testDeleteVerify(): void {
        $org = $this->model->findById($this->createdId);
        $this->assertNull($org, "Deleted organism is no longer findable");
    }

    // ── HELPER Tests ─────────────────────────────────────────────
    private function testGetAllSimple(): void {
        $simple = $this->model->getAllSimple();
        $this->assertGreaterThan(0, count($simple), "getAllSimple() returns organisms");
        $this->assert(isset($simple[0]['id']), "getAllSimple() has 'id'");
        $this->assert(isset($simple[0]['scientific_name']), "getAllSimple() has 'scientific_name'");
    }

    // ── VALIDATION Tests ─────────────────────────────────────────
    private function testCellTypeValues(): void {
        $data = [
            'kingdom' => 'Test', 'phylum' => 'Test', 'class' => 'Test',
            'order_name' => null, 'family' => null, 'genus' => null, 'species' => null,
            'scientific_name' => 'CellTest_euk_' . time(),
            'cell_type' => 'eukaryotic', 'habitat' => null, 'description' => null, 'structure_image' => null,
        ];
        $id1 = $this->model->create($data);
        $org1 = $this->model->findById($id1);
        $this->assertEqual('eukaryotic', $org1['cell_type'], "cell_type 'eukaryotic' saves correctly");

        $data['scientific_name'] = 'CellTest_prok_' . time();
        $data['cell_type'] = 'prokaryotic';
        $id2 = $this->model->create($data);
        $org2 = $this->model->findById($id2);
        $this->assertEqual('prokaryotic', $org2['cell_type'], "cell_type 'prokaryotic' saves correctly");

        // Cleanup
        $this->model->delete($id1);
        $this->model->delete($id2);
    }

    private function testNullableFields(): void {
        $data = [
            'kingdom' => 'Test', 'phylum' => 'Test', 'class' => 'Test',
            'order_name' => null, 'family' => null, 'genus' => null, 'species' => null,
            'scientific_name' => 'NullTest_' . time(),
            'cell_type' => null, 'habitat' => null, 'description' => null, 'structure_image' => null,
        ];
        $id = $this->model->create($data);
        $org = $this->model->findById($id);
        $this->assertNull($org['order_name'], "Nullable: order_name accepts NULL");
        $this->assertNull($org['family'], "Nullable: family accepts NULL");
        $this->assertNull($org['cell_type'], "Nullable: cell_type accepts NULL");
        $this->assertNull($org['habitat'], "Nullable: habitat accepts NULL");
        $this->assertNull($org['structure_image'], "Nullable: structure_image accepts NULL");
        // Cleanup
        $this->model->delete($id);
    }
}

// Run tests
$test = new OrganismTest();
$test->run();
