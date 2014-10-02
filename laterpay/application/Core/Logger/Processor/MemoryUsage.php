<?php
/**
 * Injects memory_get_usage in all records
 *
 * @see Monolog\Processor\MemoryProcessor::__construct() for options
 * @author Rob Jensen
 */
class LaterPay_Core_Logger_Processor_MemoryUsage extends LaterPay_Core_Logger_Processor_Memory {
    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record) {
        $bytes = memory_get_usage($this->real_usage);
        $formatted = $this->format_bytes($bytes);

        $record['extra'] = array_merge(
            $record['extra'],
            array(
                'memory_usage' => $formatted,
            )
        );

        return $record;
    }
}