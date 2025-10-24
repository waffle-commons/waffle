<?php

declare(strict_types=1);

namespace WaffleTests\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod; // To test the private scan method
use Waffle\Router\ControllerFinder;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(ControllerFinder::class)]
final class ControllerFinderTest extends TestCase
{
    private null|string $tempDir = null;
    private ControllerFinder $finder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = new ControllerFinder();

        // Create a unique temporary directory for file system operations
        $this->tempDir = sys_get_temp_dir() . '/waffle_finder_test_' . uniqid('', true);
        if (!mkdir($this->tempDir, 0777, true) && !is_dir($this->tempDir)) {
            $this->fail("Could not create temporary directory: {$this->tempDir}");
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Clean up the temporary directory recursively
        if ($this->tempDir && is_dir($this->tempDir)) {
            $this->recursiveDelete($this->tempDir);
        }
        $this->tempDir = null;
        parent::tearDown();
    }

    // Helper method for recursive directory deletion (copied from AbstractTestCase)
    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $scanDir = scandir($dir);
        if (!$scanDir) {
            return;
        }
        $items = array_diff($scanDir, ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }

        rmdir($dir);
    }

    public function testFindReturnsFalseForNonExistentDirectory(): void
    {
        $result = $this->finder->find($this->tempDir . '/non_existent_dir');
        static::assertFalse($result, 'find() should return false for a non-existent directory.');
    }

    public function testFindReturnsFalseForFilePath(): void
    {
        $tempFile = $this->tempDir . '/test_file.php';
        file_put_contents($tempFile, '<?php // test file');

        $result = $this->finder->find($tempFile);
        static::assertFalse($result, 'find() should return false when given a file path instead of a directory.');

        unlink($tempFile); // Clean up
    }

    public function testScanReturnsEmptyArrayForEmptyDirectory(): void
    {
        // Use reflection to make the private 'scan' method accessible for testing
        $reflectionMethod = new ReflectionMethod(ControllerFinder::class, 'scan');
        // $reflectionMethod->setAccessible(true); // No longer needed in PHP 8.1+ for public calls

        $result = $reflectionMethod->invoke($this->finder, $this->tempDir);

        static::assertIsArray($result);
        static::assertEmpty($result, 'scan() should return an empty array for an empty directory.');
    }

    public function testFindDiscoversControllersCorrectly(): void
    {
        // --- Setup directory structure and dummy files ---
        $subDir = $this->tempDir . '/SubDir';
        mkdir($subDir);

        // Valid controller in root
        $controllerAPath = $this->tempDir . '/TempControllerA.php';
        file_put_contents($controllerAPath, "<?php\n\nnamespace FinderTest;\n\nfinal class TempControllerA {}\n");

        // Valid controller in sub-directory
        $controllerBPath = $subDir . '/TempControllerB.php';
        file_put_contents(
            $controllerBPath,
            "<?php\n\nnamespace FinderTest\\SubDir;\n\nfinal class TempControllerB {}\n",
        );

        // Service file (should be ignored by className logic implicitly)
        $servicePath = $this->tempDir . '/SomeService.php';
        file_put_contents($servicePath, "<?php\n\nnamespace FinderTest;\n\nfinal class SomeService {}\n");

        // Non-PHP file (should be ignored by scan logic)
        $textPath = $this->tempDir . '/notes.txt';
        file_put_contents($textPath, 'Some notes.');

        // --- Execute ---
        // We need APP_ROOT defined for className to work relative to src/tests
        // The ControllerFinder itself doesn't need APP_ROOT if className works correctly
        $results = $this->finder->find($this->tempDir);

        // --- Assertions ---
        static::assertIsArray($results);
        static::assertCount(3, $results, 'Should find exactly three PHP classes (including services).');

        // Note: The className method relies on file content and PSR-4, not the test namespace.
        // We need to ensure the FQCNs generated match what className would produce based on file content.
        // Adjust expected FQCNs based on how ReflectionTrait::className derives them.
        // Assuming className correctly uses the namespace from the file content:
        static::assertContains('FinderTest\TempControllerA', $results);
        static::assertContains('FinderTest\SubDir\TempControllerB', $results);
        static::assertContains('FinderTest\SomeService', $results); // Service should be included for now
    }

    public function testScanIgnoresDotFiles(): void
    {
        // Create a file starting with a dot
        $dotFilePath = $this->tempDir . '/.env.local';
        file_put_contents($dotFilePath, 'TEST=true');

        // Use reflection to make the private 'scan' method accessible for testing
        $reflectionMethod = new ReflectionMethod(ControllerFinder::class, 'scan');

        $result = $reflectionMethod->invoke($this->finder, $this->tempDir);

        static::assertIsArray($result);
        static::assertEmpty($result, 'scan() should ignore files starting with a dot.');
    }

    // Note: Reliably testing scandir returning false is difficult without complex mocking
    // or filesystem manipulation (e.g., permissions). It's often considered low priority.
}
