<?php

namespace Shaonmajumder\JsonApiResponse\Traits;

trait JsonApiResponse
{
    private ?string $apiResponseTemplate = null;

    /**
     * Set a custom API response template.
     */
    protected function setApiResponseTemplate(string $template)
    {
        $this->apiResponseTemplate = $template;
    }

    /**
     * Generate a structured response using a given template.
     */
    private function respond(array $params)
    {
        if (!isset($params['message'])) {
            throw new \InvalidArgumentException('The "message" parameter is required.');
        }
        if (!isset($params['status_code'])) {
            throw new \InvalidArgumentException('The "status_code" parameter is required.');
        }
        
        $status = $params['status'] ?? false;
        $message = $params['message'] ?? 'Error';
        $code = $params['status_code'] ?? 400;
        unset($params['status_code']);

        if (isset($params['errors']) && $params['errors'] instanceof \Illuminate\Support\MessageBag) {
            $params['errors'] = $params['errors']->toArray();
        }

        if (!$this->apiResponseTemplate) {
            return response()->json(array_merge([
                'status' => $status,
                'message' => $message
            ], $params), $code);
        }
        
        $placeholdersInTemplate = $this->extractPlaceholders($this->apiResponseTemplate);
        $unusedPlaceholdersFromTemplate = array_diff($placeholdersInTemplate, array_keys($params));
        $updatedApiResponseTemplate = $this->removeUnusedPlaceholdersFromTemplate($this->apiResponseTemplate, $unusedPlaceholdersFromTemplate);
        $placeholders = $this->preparePlaceholders($params);
        $responseArray = $this->replacePlaceHolders($placeholders, $updatedApiResponseTemplate);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(json_encode([
                'error' => 'Error generating response template',
                'json_error' => json_last_error_msg(),
                'file' => __FILE__,
                'line' => __LINE__,
                'template' => $this->apiResponseTemplate,
                'invalid_json' => $responseArray
            ], JSON_PRETTY_PRINT));
        }

        array_walk_recursive($responseArray, function (&$value) {
            if ($value === 'true') {
                $value = true;
            } elseif ($value === 'false') {
                $value = false;
            }
        });

        return response()->json($responseArray, $code);
    }

    private function preparePlaceholders($params){
        $placeholders = [];
        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                $placeholders["#{$key}#"] = json_encode($value);
            } elseif (is_array($value)) {
                $placeholders["#{$key}#"] = json_encode($value, JSON_UNESCAPED_SLASHES);
            } else {
                $placeholders["#{$key}#"] = $value;
            }
        }
        return $placeholders;
    }

    /**
     * Success Response.
     */
    protected function success(array $params = [])
    {
        $params['status'] = true;
        return $this->respond($params);
    }

    /**
     * Error Response.
     */
    protected function error(array $params = [])
    {
        $params['status'] = false;
        return $this->respond($params);
    }

    /**
     * Extract all #param# placeholders from a JSON template.
     */
    private function extractPlaceholders(string $template): array
    {
        preg_match_all('/#(.*?)#/', $template, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Remove unused placeholders from the template.
     */
    private function removeUnusedPlaceholdersFromTemplate(string $template, array $unusedPlaceholders): string
    {
        foreach ($unusedPlaceholders as $placeholder) {
            // Remove both JSON keys and placeholders safely
            // $template = preg_replace('/,\s*"?' . $placeholder . '"?\s*:\s*"?#' . $placeholder . '#"?(,?)/', '', $template);
            $template = preg_replace('/\s*"?' . $placeholder . '"?\s*:\s*"?#' . $placeholder . '#"?(,?)/', '', $template);
        }
        
        // Remove any remaining trailing commas
        $template = preg_replace('/,\s*}/', '}', $template);
        $template = preg_replace('/,\s*]/', ']', $template);

        return $template;
    }

    private function replacePlaceHolders($placeholders, $apiResponseTemplate){
        $responseJson = str_replace(array_keys($placeholders), array_values($placeholders),$apiResponseTemplate);
        $responseJson = preg_replace('/\s+/', ' ', $responseJson);
        $responseJson = trim($responseJson);
        $responseArray = json_decode($responseJson, true);
        return $responseArray;
    }
}
