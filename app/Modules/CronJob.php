<?php

namespace AlexDashkin\Adwpfw\Modules;

/**
 * name*, callback*, interval, parallel
 */
class CronJob extends Module
{
    /**
     * Init Module.
     * Main App runs on "init" with 0 priority.
     * Run on "init" with the default 10 priority to run the Job after the App has fully constructed.
     */
    public function init()
    {
        $this->addHook('init', [$this, 'run']);
    }

    /**
     * Run Job
     */
    public function run()
    {
        // If we are not in WP Cron - return
        if (!defined('DOING_CRON') || !DOING_CRON) {
            return;
        }

        // Current Job Name
        $jobName = $this->getProp('name');

        // Cron option value for current job
        $option = $this->getOption($jobName);

        // Get currently running and last run params
        $running = $option['running'] ?? [];
        $lastRun = $option['last'] ?? 0;

        // If interval is not expired - exit
        if ($lastRun && (time() - $this->getProp('interval')) < $lastRun) {
            return;
        }

        // Run the Job
        $this->log("Launching cron job $jobName");

        $startTime = time();

        // Remove old possible dead jobs from "running"
        foreach ($running as $index => $ts) {
            if (($ts + 3600) < $startTime) {
                $this->log('Found dead entry started at %s, deleting', [date('Y-m-d H:i:s', $ts)]);
                unset($running[$index]);
            }
        }

        // If another process is running and parallel is disabled - abort
        if ($running && !$this->getProp('parallel')) {
            $this->log('Another instance is running, aborting');
            return;
        }

        // Add current process to "running" array
        $running[] = $startTime;

        // Update Cron Option before launching the job
        $this->updateOption(
            $jobName,
            [
                'last' => $startTime,
                'running' => $running
            ]
        );

        // Try to run the job
        try {
            $this->getProp('callback')();
        } catch (\Exception $e) {
            $msg = 'Exception: ' . $e->getMessage() . '. Execution aborted.';
            $this->log($msg);
        } finally {
            $option = $this->getOption($jobName);
            $running = $option['running'] ?? [];

            // Remove the current process from "running"
            foreach ($running as $index => $time) {
                if ($time === $startTime) {
                    unset($running[$index]);
                }
            }

            // Update Cron Option
            $this->updateOption(
                $jobName,
                [
                    'last' => $startTime,
                    'running' => $running
                ]
            );
        }

        $this->log('Done');
    }

    /**
     * Get Cron option
     *
     * @param string $name Param name
     * @return array
     */
    private function getOption(string $name): array
    {
        $option = $this->main->getOption('cron') ?: [];

        return !empty($option[$name]) && is_array($option[$name]) ? $option[$name] : [];
    }

    /**
     * Update Cron option
     *
     * @param string $name Param name
     * @param array $value Value
     */
    private function updateOption(string $name, array $value)
    {
        $option = $this->main->getOption('cron') ?: [];

        $option[$name] = $value;

        $this->main->updateOption('cron', $option);
    }

    /**
     * Get Default prop values
     *
     * @return array
     */
    protected function defaults(): array
    {
        return [
            'name' => 'cron_job',
            'interval' => 0,
        ];
    }
}
