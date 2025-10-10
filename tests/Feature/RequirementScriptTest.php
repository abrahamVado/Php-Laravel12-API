<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RequirementScriptTest extends TestCase
{
    // //1.- Validate the manifest lists the core dependencies needed for local development.
    public function test_requirements_manifest_lists_core_services(): void
    {
        // //1.- Resolve the manifest path and ensure it exists.
        $manifestPath = base_path('docs/environment/requirements.txt');
        $filesystem = new Filesystem();
        $this->assertTrue($filesystem->exists($manifestPath));

        // //2.- Read the manifest content and confirm it enumerates critical services.
        $manifest = $filesystem->get($manifestPath);
        $this->assertStringContainsString('PostgreSQL', $manifest);
        $this->assertStringContainsString('Redis', $manifest);
        $this->assertStringContainsString('Mailpit', $manifest);
        $this->assertStringContainsString('Docker Engine', $manifest);
    }

    // //2.- Ensure the helper script outputs the manifest content without checking binaries when instructed.
    public function test_check_requirements_script_outputs_manifest_without_checks(): void
    {
        // //1.- Create the command that bypasses binary validation through the environment flag.
        $scriptPath = base_path('scripts/check_requirements.sh');
        $process = Process::fromShellCommandline('CHECK_REQUIREMENTS_SKIP_COMMANDS=1 bash "'.$scriptPath.'"', base_path());

        // //2.- Run the process and gather its output for assertions.
        $process->run();
        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());

        // //3.- Confirm the rendered output includes the expected sections and entries.
        $output = $process->getOutput();
        $this->assertStringContainsString('SERVICES', $output);
        $this->assertStringContainsString(' - PostgreSQL (required: 16.x)', $output);
        $this->assertStringContainsString('TOOLING', $output);
        $this->assertStringContainsString(' - Docker Engine (required: 24+)', $output);
    }
}
