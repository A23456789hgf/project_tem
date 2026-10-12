<?php

$files = [
    'app/Models/Correspondence.php',
    'app/Models/CorrespondenceReferral.php',
    'app/Models/CorrespondenceMovement.php',
    'app/Models/CorrespondenceForwarding.php',
    'app/Models/CorrespondenceReply.php',
];

// Helper to patch models
function patch_model($filePath)
{
    if (! file_exists($filePath)) {
        return;
    }
    $content = file_get_contents($filePath);

    // 1. Add Authorities Relationships
    if (strpos($content, 'public function senderAuthority()') === false) {
        if (strpos($content, 'senderEntity()') !== false) {
            $replacement = <<<PHP
    public function senderAuthority()
    {
        return \$this->belongsTo(\App\Models\Authority::class, 'sender_authority_id');
    }

    public function getSenderOrganizationAttribute()
    {
        return app(\App\Services\CorrespondenceOrganizationResolver::class)->resolveSender(\$this);
    }

    public function senderEntity()
PHP;
            $content = str_replace('    public function senderEntity()', $replacement, $content);
        }
    }

    if (strpos($content, 'public function recipientAuthority()') === false) {
        if (strpos($content, 'recipientEntity()') !== false) {
            $replacement = <<<PHP
    public function recipientAuthority()
    {
        return \$this->belongsTo(\App\Models\Authority::class, 'recipient_authority_id');
    }

    public function getRecipientOrganizationAttribute()
    {
        return app(\App\Services\CorrespondenceOrganizationResolver::class)->resolveRecipient(\$this);
    }

    public function recipientEntity()
PHP;
            $content = str_replace('    public function recipientEntity()', $replacement, $content);
        }
    }

    if (strpos($content, 'public function creatorAuthority()') === false) {
        if (strpos($content, 'creatorEntity()') !== false) {
            $replacement = <<<PHP
    public function creatorAuthority()
    {
        return \$this->belongsTo(\App\Models\Authority::class, 'creator_authority_id');
    }

    public function getCreatorOrganizationAttribute()
    {
        return app(\App\Services\CorrespondenceOrganizationResolver::class)->resolveCreator(\$this);
    }

    public function creatorEntity()
PHP;
            $content = str_replace('    public function creatorEntity()', $replacement, $content);
        }
    }

    // Replace $this->sender_entity_id == $user->entity_id || $this->recipient_entity_id == $user->entity_id
    $resolverLogic = <<<PHP
        \$orgResolver = app(\App\Services\CorrespondenceOrganizationResolver::class);
        \$userOrg = app(\App\Services\OrganizationResolver::class)->resolve(\$user);

        if (\$orgResolver->matchesUserOrganization(\$userOrg, \$this->sender_type, \$this->sender_entity_id, \$this->sender_authority_id) ||
            \$orgResolver->matchesUserOrganization(\$userOrg, \$this->recipient_type, \$this->recipient_entity_id, \$this->recipient_authority_id)) {
PHP;
    $content = preg_replace('/if \(\$this->sender_entity_id == \$user->entity_id \|\| \$this->recipient_entity_id == \$user->entity_id\) \{/', $resolverLogic, $content);

    // Replace referrals logic
    $referralsLogic = <<<'PHP'
        if ($this->referrals()->where(function($q) use ($userOrg) {
            if ($userOrg['type'] === 'external') {
                $q->where('referred_to_type', 'external')->where('referred_to_authority_id', $userOrg['id']);
            } else {
                $q->where(function($sq) use ($userOrg) {
                    $sq->where('referred_to_type', 'internal')->orWhereNull('referred_to_type');
                })->where('referred_to_entity_id', $userOrg['id']);
            }
        })->exists()) {
PHP;
    $content = preg_replace('/if \(\$this->referrals\(\)->where\(\'referred_to_entity_id\', \$user->entity_id\)->exists\(\)\) \{/', $referralsLogic, $content);

    // In getStatusLabelAttribute
    $statusLogic = <<<PHP
        \$user = auth()->user();
        \$userOrg = \$user ? app(\App\Services\OrganizationResolver::class)->resolve(\$user) : null;
        \$orgResolver = app(\App\Services\CorrespondenceOrganizationResolver::class);

        if (\$this->status === 'pending') {
            if (\$user && \$orgResolver->matchesUserOrganization(\$userOrg, \$this->sender_type, \$this->sender_entity_id, \$this->sender_authority_id)) {
PHP;
    $content = preg_replace('/\$user = auth\(\)->user\(\);\s+if \(\$this->status === \'pending\'\) \{\s+if \(\$user && \$this->sender_entity_id == \$user->entity_id\) \{/', $statusLogic, $content);

    // For CorrespondenceReferral specifically
    if (strpos($filePath, 'CorrespondenceReferral') !== false) {
        $content = str_replace(
            'if ($this->referred_to_entity_id === $user->entity_id)',
            "\$userOrg = app(\App\Services\OrganizationResolver::class)->resolve(\$user);\n        \$orgResolver = app(\App\Services\CorrespondenceOrganizationResolver::class);\n        if (\$orgResolver->matchesUserOrganization(\$userOrg, \$this->referred_to_type, \$this->referred_to_entity_id, \$this->referred_to_authority_id))",
            $content
        );
        $content = preg_replace('/if \(\$correspondence->sender_entity_id === \$user->entity_id \|\|\s+\$correspondence->recipient_entity_id === \$user->entity_id\) \{/', $resolverLogic, $content);
    }

    // For CorrespondenceReply
    if (strpos($filePath, 'CorrespondenceReply') !== false) {
        $content = preg_replace('/if \(\$correspondence->sender_entity_id === \$user->entity_id \|\|\s+\$correspondence->recipient_entity_id === \$user->entity_id\) \{/', $resolverLogic, $content);
        $content = str_replace(
            'if ($referral && $referral->referred_to_entity_id === $user->entity_id)',
            "\$orgResolver = app(\App\Services\CorrespondenceOrganizationResolver::class);\n            if (\$referral && \$orgResolver->matchesUserOrganization(\$userOrg, \$referral->referred_to_type, \$referral->referred_to_entity_id, \$referral->referred_to_authority_id))",
            $content
        );
    }

    file_put_contents($filePath, $content);
}

foreach ($files as $file) {
    patch_model(__DIR__.'/'.$file);
}

// Policies
$policyPath = __DIR__.'/app/Policies/CorrespondencePolicy.php';
$content = file_get_contents($policyPath);

$replacement = <<<PHP
    /**
     * Determine whether the user can forward the correspondence.
     */
    public function forward(User \$user, Correspondence \$correspondence): bool
    {
        \$userOrg = app(\App\Services\OrganizationResolver::class)->resolve(\$user);
        \$orgResolver = app(\App\Services\CorrespondenceOrganizationResolver::class);
        
        return \$user->hasPermission('correspondence.forward', \$correspondence) &&
               (\$orgResolver->matchesUserOrganization(\$userOrg, \$correspondence->sender_type, \$correspondence->sender_entity_id, \$correspondence->sender_authority_id) || 
                \$orgResolver->matchesUserOrganization(\$userOrg, \$correspondence->recipient_type, \$correspondence->recipient_entity_id, \$correspondence->recipient_authority_id));
    }

    /**
     * Determine whether the user can close the correspondence.
     */
    public function close(User \$user, Correspondence \$correspondence): bool
    {
        \$userOrg = app(\App\Services\OrganizationResolver::class)->resolve(\$user);
        \$orgResolver = app(\App\Services\CorrespondenceOrganizationResolver::class);
        
        return \$user->hasPermission('correspondence.close', \$correspondence) &&
               (\$orgResolver->matchesUserOrganization(\$userOrg, \$correspondence->sender_type, \$correspondence->sender_entity_id, \$correspondence->sender_authority_id) || 
                \$orgResolver->matchesUserOrganization(\$userOrg, \$correspondence->recipient_type, \$correspondence->recipient_entity_id, \$correspondence->recipient_authority_id));
    }
PHP;

$content = preg_replace('/\/\*\*[\s\*]*Determine whether the user can forward the correspondence.*?public function close\(User \$user, Correspondence \$correspondence\): bool\s+\{[\s\S]*?\}/m', $replacement, $content);

file_put_contents($policyPath, $content);

echo 'Patched successfully!';
