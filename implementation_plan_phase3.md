# Phase 3: Correspondence External Organization Implementation Plan

## 1. Database Migration
- **Target Tables**: `correspondences`, `correspondence_referrals`, `correspondence_movements`, `correspondence_forwardings`.
- **Fields to Add**:
  - `correspondences`: `sender_type` (string), `sender_authority_id` (foreign key, nullable), `recipient_type` (string), `recipient_authority_id` (foreign key, nullable), `creator_type` (string), `creator_authority_id` (foreign key, nullable).
  - `correspondence_referrals`: `referred_to_type` (string), `referred_to_authority_id` (foreign key, nullable).
  - `correspondence_movements`: `from_type` (string), `from_authority_id` (foreign key, nullable), `to_type` (string), `to_authority_id` (foreign key, nullable).
  - `correspondence_forwardings`: `from_type` (string), `from_authority_id` (foreign key, nullable), `to_type` (string), `to_authority_id` (foreign key, nullable).
- **Defaulting/Safety**: Existing records will naturally fall back to `internal` logic due to the nullability of these new fields. We will ensure the old data remains intact.

## 2. Organization Layer Extension
- **Action**: Create or extend the `OrganizationResolver` to include unified getters for Correspondence models, returning a standard format `['type' => '...', 'id' => ..., 'model' => ...]`.

## 3. Models Update
- **Relations**: Add `senderAuthority()`, `recipientAuthority()`, `creatorAuthority()`, etc. to the respective models.
- **Accessors**: Implement `getSenderOrganizationAttribute()`, `getRecipientOrganizationAttribute()` to return the correct model instance (`InternalEntity` or `Authority`) based on `_type`.

## 4. Policies and Authorization
- **Action**: Refactor `CorrespondencePolicy`, `Correspondence`, `CorrespondenceReferral`, and `CorrespondenceReply`.
- **Logic**: Replace `$user->entity_id` checks with checks against `OrganizationResolver::resolve($user)`. For example: `organization_type === $correspondence->sender_type && organization_id === $correspondence->sender_entity_id/authority_id`.

## 5. Creation Logic (Controllers/Services)
- **Action**: Update `CorrespondenceController` and `CorrespondenceService` to automatically populate `sender_type` and `sender_*_id` based on the creator's resolved organization.

## 6. Recipient Selection
- **Action**: Update the API/Views responsible for fetching correspondence recipients.
- **Filtering Logic**: 
  - Internal users see internal entities.
  - External users see Authorities (Administrative scope) + InternalEntities/Authorities (Geographic scope).

## 7. Movement, Referral, and Forwarding
- **Action**: Update `CorrespondenceMovement`, `Referral`, and `Forwarding` creation logic to capture `from_type`/`from_id` and `to_type`/`to_id` fully.

## 8. Migration Safety & Legacy Data
- **Action**: Write a fallback in accessors and policies that treats `null` type values as `internal` to guarantee legacy data works seamlessly.

## 9. Feature Tests
- **Action**: Write comprehensive tests:
  - Internal User sends correspondence.
  - External User sends correspondence.
  - Internal -> External.
  - External -> Internal.
  - External Authority Scope isolation.
  - Geographic Scope filtering.
  - Forwarding, Reply, Movement history.
  - Legacy internal correspondence regression.
