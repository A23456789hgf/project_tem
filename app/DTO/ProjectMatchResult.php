<?php

namespace App\DTO;

use App\Models\Project;

/**
 * Consistent DTO structure for project matching results.
 */
class ProjectMatchResult implements \JsonSerializable
{
    public ?Project $project;

    public string $status; // matched_exact|matched_normalized|matched_fuzzy|needs_review|ambiguous|not_found

    public ?float $score; // similarity score (0.0 - 1.0)

    public string $method; // exact|normalized|fuzzy

    public array $candidates; // array of ['id' => int, 'project_name' => string, 'score' => float]

    public ?string $reason; // human readable explanation

    public function __construct(
        ?Project $project = null,
        string $status = 'not_found',
        ?float $score = null,
        string $method = '',
        ?array $candidates = [],
        ?string $reason = null,
    ) {
        $this->project = $project;
        $this->status = $status;
        $this->score = $score;
        $this->method = $method;
        $this->candidates = $candidates ?? [];
        $this->reason = $reason;
    }

    public function isMatch(): bool
    {
        return in_array($this->status, ['matched_exact', 'matched_normalized', 'matched_fuzzy'], true) && $this->project !== null;
    }

    public function jsonSerialize(): array
    {
        return [
            'project' => $this->project ? $this->project->id : null,
            'status' => $this->status,
            'score' => $this->score,
            'method' => $this->method,
            'candidates' => $this->candidates,
            'reason' => $this->reason,
        ];
    }
}
