const sensorForm = document.querySelector('[data-sensor-form]');

if (sensorForm) {
  const typeSelect = sensorForm.querySelector('[name="tipo"]');
  const resourceTypeSelect = sensorForm.querySelector('[name="recurso_tipo"]');
  const resourceSelect = sensorForm.querySelector('[name="recurso_id"]');
  const buildingSelect = sensorForm.querySelector('[name="edificio"]');
  const floorInput = sensorForm.querySelector('[name="piso"]');
  const preview = {
    code: document.querySelector('[data-preview-code]'),
    type: document.querySelector('[data-preview-type]'),
    location: document.querySelector('[data-preview-location]'),
    building: document.querySelector('[data-preview-building]'),
    unit: document.querySelector('[data-preview-unit]')
  };

  const typeSettings = {
    temperatura: { label: 'Temperatura', unit: '°C' },
    co2: { label: 'Qualidade do Ar (CO₂)', unit: 'ppm' },
    humidade: { label: 'Humidade', unit: '%' },
    energia: { label: 'Energia', unit: 'kW' }
  };

  const getValue = (name, fallback) => {
    return sensorForm.elements[name]?.value.trim() || fallback;
  };

  const selectedResource = () => {
    return resourceSelect?.options[resourceSelect.selectedIndex] || null;
  };

  const updatePreview = () => {
    const type = typeSettings[typeSelect?.value] || typeSettings.temperatura;
    const resource = selectedResource();
    const resourceText = resource?.value ? resource.textContent.trim() : 'Por definir';
    const buildingText = resource?.dataset.buildingLabel || buildingSelect?.options[buildingSelect.selectedIndex]?.text || 'Por definir';

    preview.code.textContent = getValue('codigo', 'Por definir');
    preview.type.textContent = type.label;
    preview.location.textContent = resourceText;
    preview.building.textContent = buildingText;
    preview.unit.textContent = type.unit;
  };

  const updateResourceFromFilters = () => {
    if (!resourceSelect || resourceSelect.disabled) {
      updatePreview();
      return;
    }

    const selectedType = resourceTypeSelect?.value || 'sala';
    let hasSelectedResource = false;

    Array.from(resourceSelect.options).forEach((option) => {
      if (!option.value) {
        return;
      }

      const matchesType = option.dataset.resourceType === selectedType;
      option.hidden = !matchesType;
      option.disabled = !matchesType;

      if (matchesType && option.selected) {
        hasSelectedResource = true;
      }
    });

    if (!hasSelectedResource) {
      resourceSelect.value = '';
      if (floorInput) {
        floorInput.value = '';
      }
    }

    const resource = selectedResource();
    if (resource?.dataset.building && buildingSelect) {
      buildingSelect.value = resource.dataset.building;
    }

    if (floorInput) {
      floorInput.value = resource?.dataset.floor || '';
    }

    updatePreview();
  };

  const syncFiltersFromSelectedResource = () => {
    const resource = selectedResource();

    if (resource?.dataset.resourceType && resourceTypeSelect) {
      resourceTypeSelect.value = resource.dataset.resourceType;
    }

    if (resource?.dataset.building && buildingSelect) {
      buildingSelect.value = resource.dataset.building;
    }

    if (floorInput) {
      floorInput.value = resource?.dataset.floor || '';
    }

    updateResourceFromFilters();
  };

  sensorForm.addEventListener('input', updatePreview);
  sensorForm.addEventListener('change', updatePreview);
  typeSelect?.addEventListener('change', updatePreview);
  resourceTypeSelect?.addEventListener('change', updateResourceFromFilters);
  resourceSelect?.addEventListener('change', syncFiltersFromSelectedResource);

  updateResourceFromFilters();
}
