<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../admin/api/actions_helper.php';

class TestTriggerDeployPhpUnit extends TestCase
{
    public function test_missing_repo_returns_error()
    {
        $res = trigger_github_workflow('', 'deploy-to-cpanel.yml', 'main', 'fake-token');
        $this->assertFalse($res['ok']);
        $this->assertEquals('missing_repo', $res['message']);
    }

    public function test_missing_token_returns_error()
    {
        $res = trigger_github_workflow('owner/repo', 'deploy-to-cpanel.yml', 'main', null);
        $this->assertFalse($res['ok']);
        $this->assertEquals('missing_token', $res['message']);
    }
}
