@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="fas fa-plus-circle me-2"></i>إضافة جهة مشاركة - {{ $valueChain->name }}
                    </h5>
                    <a href="{{ route('value-chains.participating-entities.index', $valueChain) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i> عودة
                    </a>
                </div>

                <div class="card-body p-4">
                    @can('value_chains.create')
                    <form action="{{ route('value-chains.participating-entities.store', $valueChain) }}" method="POST">
                    @endcan
                        @csrf
                        <div class="row">

                            {{-- نوع الجهة --}}
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">نوع الجهة <span class="text-danger">*</span></label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="entity_type" id="entity_type_internal"
                                            value="internal" {{ old('entity_type', 'internal') == 'internal' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="entity_type_internal">جهة داخلية</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="entity_type" id="entity_type_external"
                                            value="external" {{ old('entity_type') == 'external' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="entity_type_external">جهة خارجية</label>
                                    </div>
                                </div>
                                @error('entity_type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- الجهة الداخلية --}}
                            <div class="col-md-12 mb-3" id="internal_entity_container" style="{{ old('entity_type', 'internal') == 'internal' ? '' : 'display: none;' }}">
                                <label for="internal_entity_id" class="form-label fw-bold">الجهة الداخلية <span class="text-danger">*</span></label>
                                <select class="form-select @error('internal_entity_id') is-invalid @enderror" 
                                    id="internal_entity_id" name="internal_entity_id">
                                    <option value="">-- اختر الجهة --</option>
                                    @foreach($internalEntities as $entity)
                                        <option value="{{ $entity->id }}" @selected(old('internal_entity_id') == $entity->id)>
                                            {{ $entity->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('internal_entity_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- الجهة الخارجية --}}
                            <div class="col-md-12 mb-3" id="external_entity_container" style="{{ old('entity_type') == 'external' ? '' : 'display: none;' }}">
                                <label for="authority_id" class="form-label fw-bold">الجهة الخارجية <span class="text-danger">*</span></label>
                                <select class="form-select @error('authority_id') is-invalid @enderror" 
                                    id="authority_id" name="authority_id">
                                    <option value="">-- اختر الجهة --</option>
                                    @foreach($authorities as $authority)
                                        <option value="{{ $authority->id }}" @selected(old('authority_id') == $authority->id)>
                                            {{ $authority->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('authority_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4 me-2">
                                <i class="fas fa-save me-1"></i> حفظ
                            </button>
                            <a href="{{ route('value-chains.participating-entities.index', $valueChain) }}" class="btn btn-light px-4">
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const internalRadio = document.getElementById('entity_type_internal');
        const externalRadio = document.getElementById('entity_type_external');
        const internalContainer = document.getElementById('internal_entity_container');
        const externalContainer = document.getElementById('external_entity_container');

        function toggleEntityFields() {
            if (internalRadio.checked) {
                internalContainer.style.display = 'block';
                externalContainer.style.display = 'none';
            } else {
                internalContainer.style.display = 'none';
                externalContainer.style.display = 'block';
            }
        }

        internalRadio.addEventListener('change', toggleEntityFields);
        externalRadio.addEventListener('change', toggleEntityFields);
    });
</script>
@endsection
