<?php

namespace App\Traits;

use App\Enums\MacroBatchStatusEnum;
use App\Models\Royalty\MacroBatch;
use Exception;
use Illuminate\Support\Facades\Log;

trait ErrorLogger
{
    /**
     * Log an error to the MacroBatch errors field and Laravel log
     *
     * @param int $batchId
     * @param Exception|string $error
     * @param string $context
     * @param string $severity
     * @return void
     */
    public function logErrorToMacroBatch($batchId, $error, $context = '', $severity = 'error')
    {
        $errorMessage = $error instanceof Exception ? $error->getMessage() : (string) $error;
        $errorTrace = $error instanceof Exception ? $error->getTraceAsString() : '';
        
        $errorEntry = [
            'timestamp' => now()->toISOString(),
            'context' => $context,
            'severity' => $severity,
            'message' => $errorMessage,
            'class' => get_class($this),
        ];
        
        if ($errorTrace) {
            $errorEntry['trace'] = $errorTrace;
        }
        
        try {
            $macroBatch = MacroBatch::find($batchId);
            if ($macroBatch) {
                $existingErrors = $macroBatch->errors ? json_decode($macroBatch->errors, true) : [];
                if (!is_array($existingErrors)) {
                    $existingErrors = [];
                }
                
                $existingErrors[] = $errorEntry;
                $macroBatch->errors = json_encode($existingErrors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $macroBatch->save();
            }
            
            // Also log to Laravel log for debugging
            Log::error("MacroBatch {$batchId} - {$context}: {$errorMessage}", [
                'batch_id' => $batchId,
                'context' => $context,
                'severity' => $severity,
                'class' => get_class($this),
                'error' => $error instanceof Exception ? $error : $errorMessage,
            ]);
        } catch (Exception $logException) {
            // Fallback logging if database update fails
            Log::error("Failed to log error to MacroBatch {$batchId}", [
                'batch_id' => $batchId,
                'original_context' => $context,
                'original_error' => $errorMessage,
                'log_error' => $logException->getMessage(),
            ]);
        }
    }
    
    /**
     * Mark MacroBatch as failed and log the error
     *
     * @param int $batchId
     * @param Exception|string $error
     * @param string $context
     * @return void
     */
    public function markBatchAsFailedWithError($batchId, $error, $context = '')
    {
        try {
            $this->logErrorToMacroBatch($batchId, $error, $context, 'critical');
            
            $macroBatch = MacroBatch::find($batchId);
            if ($macroBatch) {
                $macroBatch->status = MacroBatchStatusEnum::Failed()->value;
                $macroBatch->save();
            }
        } catch (Exception $logException) {
            Log::error("Failed to mark batch as failed", [
                'batch_id' => $batchId,
                'original_context' => $context,
                'original_error' => $error instanceof Exception ? $error->getMessage() : $error,
                'log_error' => $logException->getMessage(),
            ]);
        }
    }
    
    /**
     * Log a business error (non-critical) to MacroBatch
     *
     * @param int $batchId
     * @param string $message
     * @param string $context
     * @param array $additionalData
     * @return void
     */
    public function logBusinessError($batchId, $message, $context = '', $additionalData = [])
    {
        $errorEntry = [
            'timestamp' => now()->toISOString(),
            'context' => $context,
            'severity' => 'warning',
            'type' => 'business_error',
            'message' => $message,
            'class' => get_class($this),
        ];
        
        if (!empty($additionalData)) {
            $errorEntry['data'] = $additionalData;
        }
        
        try {
            $macroBatch = MacroBatch::find($batchId);
            if ($macroBatch) {
                $existingErrors = $macroBatch->errors ? json_decode($macroBatch->errors, true) : [];
                if (!is_array($existingErrors)) {
                    $existingErrors = [];
                }
                
                $existingErrors[] = $errorEntry;
                $macroBatch->errors = json_encode($existingErrors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $macroBatch->save();
            }
            
            Log::warning("MacroBatch {$batchId} - Business Error - {$context}: {$message}", [
                'batch_id' => $batchId,
                'context' => $context,
                'message' => $message,
                'additional_data' => $additionalData,
                'class' => get_class($this),
            ]);
        } catch (Exception $logException) {
            Log::error("Failed to log business error to MacroBatch {$batchId}", [
                'batch_id' => $batchId,
                'original_context' => $context,
                'original_message' => $message,
                'log_error' => $logException->getMessage(),
            ]);
        }
    }
}