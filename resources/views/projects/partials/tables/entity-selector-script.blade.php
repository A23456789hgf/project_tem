@once
    <script>
        (() => {
            const configs = [
                { table: 'beneficiaryEntitiesTable', collection: 'beneficiary_entities', button: 'addBeneficiaryEntityBtn', typeClass: 'entity-type-select', removeClass: 'remove-entity' },
                { table: 'implementingEntitiesTable', collection: 'implementing_entities', button: 'addImplementingEntityBtn', typeClass: 'entity-type-select', removeClass: 'remove-entity' },
                { table: 'participatingEntitiesTable', collection: 'participating_entities', button: 'addParticipatingEntityBtn', typeClass: 'entity-type-select', removeClass: 'remove-entity' },
                { table: 'supervisingAuthoritiesTable', collection: 'supervising_authorities', button: 'addSupervisingAuthorityBtn', typeClass: 'entity-type-select', removeClass: 'remove-authority' },
            ];
            const cache = new Map();
            const labels = {
                loading: 'Loading entities...',
                empty: { internal: 'No internal entities found', external: 'No external entities found' },
                failed: 'Failed to load entities',
                choose: 'Choose an entity',
                noParent: 'No parent entity',
            };

            const loadEntities = (url, type) => {
                const key = `${url}:${type}`;
                if (!cache.has(key)) {
                    const requestUrl = new URL(url, window.location.href);
                    requestUrl.searchParams.set('type', type);
                    cache.set(key, fetch(requestUrl.toString(), {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    }).then((response) => {
                        if (!response.ok) throw new Error('Entity options request failed');
                        return response.json();
                    }).then((payload) => {
                        if (payload.type !== type || !Array.isArray(payload.entities)) {
                            throw new Error('Entity options response did not match the requested type');
                        }

                        return payload.entities;
                    }).catch((error) => {
                        cache.delete(key);
                        throw error;
                    }));
                }
                return cache.get(key);
            };

            const tableEntities = (rows, type) => {
                const index = new Map(rows.map((entity) => [String(entity.id), entity]));
                return { rows, index, type };
            };

            const refreshSelect2 = (select) => {
                const $ = window.jQuery;
                if ($ && $.fn?.select2 && $(select).hasClass('select2-hidden-accessible')) {
                    $(select).trigger('change.select2');
                }
            };

            const fillParent = (row, option) => {
                const nameInput = row.querySelector('.parent-name-input');
                const idInput = row.querySelector('.parent-id-input');
                if (!nameInput || !idInput) return;

                nameInput.value = option?.value ? (option.dataset.parentName || labels.noParent) : labels.noParent;
                idInput.value = option?.value ? (option.dataset.parentId || '') : '';
            };

            const populate = async (table, row, type, preserveSelection = true) => {
                const select = row.querySelector('.authority-select');
                if (!select) return;

                const selectedId = preserveSelection
                    ? (select.value || select.dataset.entityId || select.dataset.value || '')
                    : '';
                const selectedLabel = select.dataset.selectedLabel || select.selectedOptions[0]?.textContent?.trim() || '';
                const url = table.dataset.entityOptionsUrl;

                select.disabled = true;
                select.replaceChildren(new Option(labels.loading, ''));
                refreshSelect2(select);

                try {
                    const rows = await loadEntities(url, type);
                    if (row.querySelector('.entity-type-select')?.value !== type) return;
                    const entities = tableEntities(rows, type);
                    select.replaceChildren(new Option(labels.choose, ''));

                    if (rows.length === 0) {
                        select.add(new Option(labels.empty[type], ''));
                    } else {
                        rows.forEach((entity) => {
                            const option = new Option(entity.name, String(entity.id));
                            option.dataset.parentId = entity.parent_id === null ? '' : String(entity.parent_id);
                            option.dataset.parentName = entity.parent_name || '';
                            select.add(option);
                        });
                    }

                    if (selectedId && !entities.index.has(String(selectedId))) {
                        const savedOption = new Option(selectedLabel || selectedId, String(selectedId));
                        select.add(savedOption);
                    }
                    select.value = selectedId;
                    select.disabled = false;
                    refreshSelect2(select);
                    fillParent(row, select.selectedOptions[0]);
                    return entities;
                } catch (error) {
                    if (row.querySelector('.entity-type-select')?.value !== type) return;
                    select.replaceChildren(new Option(labels.failed, ''));
                    select.disabled = true;
                    refreshSelect2(select);
                    throw error;
                }
            };

            const syncNames = (config, row, index) => {
                row.dataset.entityIndex = String(index);
                row.dataset.authorityIndex = String(index);
                const type = row.querySelector(`.${config.typeClass}`);
                const entity = row.querySelector('.authority-select');
                const parentName = row.querySelector('.parent-name-input');
                const parentId = row.querySelector('.parent-id-input');
                if (type) type.name = `${config.collection}[${index}][authority_type]`;
                if (entity) entity.name = `${config.collection}[${index}][${type?.value === 'external' ? 'authority_id' : 'internal_entity_id'}]`;
                if (parentName) parentName.name = `${config.collection}[${index}][parent_name]`;
                if (parentId) parentId.name = `${config.collection}[${index}][parent_id]`;
            };

            const emptyRowTemplate = (table) => table.querySelector('.project-empty-row')?.cloneNode(true);

            const makeRow = (config, index) => {
                const row = document.createElement('tr');
                row.dataset.entityIndex = String(index);
                row.dataset.authorityIndex = String(index);
                row.innerHTML = `
                    <td><select class="form-select ${config.typeClass}" name="${config.collection}[${index}][authority_type]"><option value="internal" selected>داخلية</option><option value="external">خارجية</option></select></td>
                    <td><select class="form-select authority-select" name="${config.collection}[${index}][internal_entity_id]" disabled><option value="">${labels.loading}</option></select></td>
                    <td><input type="text" name="${config.collection}[${index}][parent_name]" class="form-control parent-name-input" readonly value="${labels.noParent}"><input type="hidden" name="${config.collection}[${index}][parent_id]" class="parent-id-input"></td>
                    <td><div class="project-action-buttons"><button type="button" class="project-btn project-btn-danger ${config.removeClass}" title="حذف الجهة"><i class="fas fa-trash"></i></button></div></td>`;
                return row;
            };

            const applyOldInput = (config, row, values) => {
                const type = values.authority_type || values.entity_type || 'internal';
                const typeSelect = row.querySelector(`.${config.typeClass}`);
                const entitySelect = row.querySelector('.authority-select');
                const entityId = values[type === 'external' ? 'authority_id' : 'internal_entity_id'] || '';

                typeSelect.value = type;
                entitySelect.dataset.entityId = String(entityId);
                entitySelect.dataset.selectedLabel = String(entityId);
                entitySelect.value = String(entityId);
                row.querySelector('.parent-name-input').value = labels.noParent;
                row.querySelector('.parent-id-input').value = '';
                syncNames(config, row, Number(row.dataset.entityIndex));
            };

            const initialize = () => {
                configs.forEach((config) => {
                    const table = document.getElementById(config.table);
                    if (!table || table.dataset.entitySelectorInitialized === 'true') return;
                    table.dataset.entitySelectorInitialized = 'true';
                    table.dataset.entityOptionsUrl ||= table.closest('[data-entity-options-url]')?.dataset.entityOptionsUrl || '';

                    const tbody = table.tBodies[0];
                    const emptyTemplate = emptyRowTemplate(table);
                    let nextIndex = 0;

                    const rows = () => [...tbody.querySelectorAll('tr:not(.project-empty-row)')];
                    if (table.dataset.oldInput === 'true') {
                        tbody.replaceChildren();
                        let oldRows = [];
                        try {
                            oldRows = JSON.parse(table.dataset.oldRows || '[]');
                        } catch (error) {
                            oldRows = [];
                        }
                        if (!Array.isArray(oldRows)) oldRows = [];
                        oldRows.forEach((values, index) => {
                            const row = makeRow(config, index);
                            applyOldInput(config, row, values || {});
                            tbody.append(row);
                            nextIndex = index + 1;
                        });
                        if (oldRows.length === 0 && emptyTemplate) tbody.append(emptyTemplate);
                    }
                    rows().forEach((row) => {
                        const index = Number(row.dataset.entityIndex ?? row.dataset.authorityIndex ?? nextIndex);
                        nextIndex = Math.max(nextIndex, index + 1);
                        syncNames(config, row, index);
                        const type = row.querySelector(`.${config.typeClass}`)?.value || 'internal';
                        populate(table, row, type).catch(() => {});
                    });

                    table.closest('.project-table-container')?.querySelector(`#${config.button}`)?.addEventListener('click', () => {
                        table.querySelector('.project-empty-row')?.remove();
                        const row = makeRow(config, nextIndex++);
                        tbody.append(row);
                        populate(table, row, row.querySelector(`.${config.typeClass}`).value, false).catch(() => {});
                    });

                    const handleSelectChange = (event) => {
                        const row = event.target.closest('tbody tr');
                        if (!row) return;
                        if (event.target.matches(`.${config.typeClass}`)) {
                            syncNames(config, row, Number(row.dataset.entityIndex ?? row.dataset.authorityIndex));
                            const entitySelect = row.querySelector('.authority-select');
                            entitySelect.value = '';
                            delete entitySelect.dataset.entityId;
                            delete entitySelect.dataset.value;
                            const parentName = row.querySelector('.parent-name-input');
                            const parentId = row.querySelector('.parent-id-input');
                            if (parentName) parentName.value = labels.noParent;
                            if (parentId) parentId.value = '';
                            entitySelect.disabled = true;
                            refreshSelect2(entitySelect);
                            populate(table, row, event.target.value, false).catch(() => {});
                        } else if (event.target.matches('.authority-select')) {
                            fillParent(row, event.target.selectedOptions[0]);
                        }
                    };

                    if (window.jQuery) {
                        window.jQuery(table).on('change.projectEntitySelector', '.entity-type-select, .authority-select', handleSelectChange);
                    } else {
                        table.addEventListener('change', handleSelectChange);
                    }

                    table.addEventListener('click', (event) => {
                        const button = event.target.closest(`.${config.removeClass}`);
                        if (!button || !table.contains(button)) return;
                        button.closest('tr')?.remove();
                        if (rows().length === 0 && emptyTemplate) tbody.append(emptyTemplate.cloneNode(true));
                    });
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initialize, { once: true });
            } else {
                initialize();
            }
        })();
    </script>
@endonce
