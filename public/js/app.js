window.AppForms = {
  bindLocationFilters: function (departmentId, municipalityId, communityId = null) {
    const department = document.getElementById(departmentId);
    const municipality = document.getElementById(municipalityId);
    const community = communityId ? document.getElementById(communityId) : null;
    if (!department || !municipality) return;

    const municipalityOptions = Array.from(municipality.options);
    const communityOptions = community ? Array.from(community.options) : [];

    const refreshMunicipalities = () => {
      const selectedDepartment = department.value;
      const previous = municipality.value;
      municipality.innerHTML = '';
      municipalityOptions.forEach(option => {
        if (!option.value || !selectedDepartment || option.dataset.department === selectedDepartment) {
          municipality.appendChild(option.cloneNode(true));
        }
      });

      municipality.value = Array.from(municipality.options).some(o => o.value === previous) ? previous : '';
      if (community) refreshCommunities();
    };

    const refreshCommunities = () => {
      if (!community) return;
      const selectedDepartment = department.value;
      const selectedMunicipality = municipality.value;
      const previous = community.value;
      community.innerHTML = '';
      communityOptions.forEach(option => {
        if (
          !option.value ||
          (
            (!selectedDepartment || option.dataset.department === selectedDepartment) &&
            (!selectedMunicipality || option.dataset.municipality === selectedMunicipality)
          )
        ) {
          community.appendChild(option.cloneNode(true));
        }
      });

      community.value = Array.from(community.options).some(o => o.value === previous) ? previous : '';
    };

    department.addEventListener('change', refreshMunicipalities);
    municipality.addEventListener('change', refreshCommunities);
    refreshMunicipalities();
  },

  bindLeaderFilters: function (departmentId, municipalityId, communityId, leaderId) {
    const department = document.getElementById(departmentId);
    const municipality = document.getElementById(municipalityId);
    const community = document.getElementById(communityId);
    const leader = document.getElementById(leaderId);
    if (!department || !municipality || !leader) return;

    const leaderOptions = Array.from(leader.options);

    const refresh = () => {
      const selectedDepartment = department.value;
      const selectedMunicipality = municipality.value;
      const selectedCommunity = community ? community.value : '';
      const previous = leader.value;
      leader.innerHTML = '';

      leaderOptions.forEach(option => {
        if (!option.value) {
          leader.appendChild(option.cloneNode(true));
          return;
        }

        const sameDepartment = !selectedDepartment || option.dataset.department === selectedDepartment;
        const sameMunicipality = !selectedMunicipality || option.dataset.municipality === selectedMunicipality;
        const sameCommunity = !selectedCommunity || !option.dataset.community || option.dataset.community === selectedCommunity;

        if (sameDepartment && sameMunicipality && sameCommunity) {
          leader.appendChild(option.cloneNode(true));
        }
      });

      leader.value = Array.from(leader.options).some(o => o.value === previous) ? previous : '';
    };

    department.addEventListener('change', refresh);
    municipality.addEventListener('change', refresh);
    if (community) community.addEventListener('change', refresh);
    refresh();
  },

  bindPositionRules: function (positionId, departmentId, municipalityId, slotId) {
    const position = document.getElementById(positionId);
    const department = document.getElementById(departmentId);
    const municipality = document.getElementById(municipalityId);
    const slot = document.getElementById(slotId);
    if (!position) return;

    const refresh = () => {
      const selected = position.options[position.selectedIndex];
      if (!selected) return;

      const reqDepartment = selected.dataset.requiresDepartment === '1';
      const reqMunicipality = selected.dataset.requiresMunicipality === '1';
      const reqSlot = selected.dataset.requiresSlot === '1';

      if (department) department.required = reqDepartment;
      if (municipality) municipality.required = reqMunicipality;
      if (slot) {
        slot.required = reqSlot;
        slot.min = selected.dataset.slotMin || '';
        slot.max = selected.dataset.slotMax || '';
      }
    };

    position.addEventListener('change', refresh);
    refresh();
  }
};
