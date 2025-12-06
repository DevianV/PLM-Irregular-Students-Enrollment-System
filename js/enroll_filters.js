/**
 * Search and Filter Functions for Enlistment Page
 */

// Filter subjects based on search and filters
function filterSubjects() {
    try {
        const searchInput = document.getElementById('subjectSearch');
        const yearFilterSelect = document.getElementById('yearFilter');
        const unitsFilterSelect = document.getElementById('unitsFilter');
        
        if (!searchInput) {
            return;
        }
        
        const searchTerm = (searchInput.value || '').toLowerCase().trim();
        const yearFilter = yearFilterSelect ? yearFilterSelect.value : '';
        const unitsFilter = unitsFilterSelect ? unitsFilterSelect.value : '';
        
        // Get table rows
        const tableRows = document.querySelectorAll('#availableSubjectsTable tbody tr');
        let visibleCount = 0;
        
        if (tableRows.length > 0) {
            // Process each row
            tableRows.forEach(row => {
                // Get all text from the row's cells (Subject Code, Course Title, Units, Pre/Co-Requisites)
                const cells = row.querySelectorAll('td');
                
                // Collect all searchable text from the row
                let searchableText = '';
                
                // Get Subject Code (first cell) - remove badge if present
                if (cells[0]) {
                    const subjectCodeCell = cells[0].cloneNode(true);
                    const badges = subjectCodeCell.querySelectorAll('.cross-program-badge');
                    badges.forEach(badge => badge.remove());
                    searchableText += ' ' + subjectCodeCell.textContent.toLowerCase().trim();
                }
                
                // Get Course Title (second cell)
                if (cells[1]) {
                    searchableText += ' ' + cells[1].textContent.toLowerCase().trim();
                }
                
                // Get Units (third cell)
                if (cells[2]) {
                    searchableText += ' ' + cells[2].textContent.toLowerCase().trim();
                }
                
                // Get Pre/Co-Requisites (fourth cell)
                if (cells[3]) {
                    searchableText += ' ' + cells[3].textContent.toLowerCase().trim();
                }
                
                // Also check data attributes as backup
                const dataSubjectCode = (row.getAttribute('data-subject-code') || '').toLowerCase();
                const dataSubjectName = (row.getAttribute('data-subject-name') || '').toLowerCase();
                const dataUnits = (row.getAttribute('data-units') || '').toLowerCase();
                searchableText += ' ' + dataSubjectCode + ' ' + dataSubjectName + ' ' + dataUnits;
                
                // Get filter values from data attributes
                const yearLevel = row.getAttribute('data-year-level') || '';
                const unitsValue = row.getAttribute('data-units') || '';
                
                // Check if search term matches
                const matchesSearch = !searchTerm || searchableText.includes(searchTerm);
                
                // Check year filter
                const matchesYear = !yearFilter || yearLevel === yearFilter;
                
                // Check units filter
                const matchesUnits = !unitsFilter || unitsValue === unitsFilter;
                
                // Show/hide row
                if (matchesSearch && matchesYear && matchesUnits) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            const noResultsMsg = document.getElementById('noResultsMessage');
            if (visibleCount === 0 && tableRows.length > 0) {
                if (!noResultsMsg) {
                    const msg = document.createElement('p');
                    msg.id = 'noResultsMessage';
                    msg.className = 'no-data';
                    msg.textContent = 'No subjects match your search criteria.';
                    const container = document.querySelector('.available-subjects-container');
                    if (container) {
                        const tableContainer = container.querySelector('.subjects-table-container');
                        if (tableContainer) {
                            tableContainer.appendChild(msg);
                        } else {
                            container.appendChild(msg);
                        }
                    }
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        } else {
            // Fallback to old card structure
            const subjectCards = document.querySelectorAll('.subject-card');
            
            subjectCards.forEach(card => {
                const subjectCode = card.getAttribute('data-subject-code') || '';
                const subjectName = card.getAttribute('data-subject-name') || '';
                const yearLevel = card.getAttribute('data-year-level') || '';
                const units = card.getAttribute('data-units') || '';
                
                // Check search term
                const matchesSearch = !searchTerm || 
                    subjectCode.includes(searchTerm) || 
                    subjectName.includes(searchTerm);
                
                // Check year filter
                const matchesYear = !yearFilter || yearLevel === yearFilter;
                
                // Check units filter
                const matchesUnits = !unitsFilter || units === unitsFilter;
                
                // Show/hide card
                if (matchesSearch && matchesYear && matchesUnits) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show message if no results
            const noResultsMsg = document.getElementById('noResultsMessage');
            if (visibleCount === 0 && subjectCards.length > 0) {
                if (!noResultsMsg) {
                    const msg = document.createElement('p');
                    msg.id = 'noResultsMessage';
                    msg.className = 'no-data';
                    msg.textContent = 'No subjects match your search criteria.';
                    const container = document.querySelector('.available-subjects');
                    if (container) {
                        container.appendChild(msg);
                    }
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }
    } catch (error) {
        console.error('Error in filterSubjects:', error);
    }
}

// Show subject details
function showSubjectDetails(subjectCode) {
    fetch('php/get_subject_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `subject_code=${encodeURIComponent(subjectCode)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const content = document.getElementById('subjectDetailsContent');
            let html = '<div class="subject-details">';
            html += `<h4>${data.subject.subject_code} - ${data.subject.subject_name}</h4>`;
            html += `<p><strong>Program:</strong> ${data.subject.program}</p>`;
            html += `<p><strong>Units:</strong> ${data.subject.units}</p>`;
            html += `<p><strong>Semester:</strong> ${data.subject.semester}</p>`;
            
            if (data.prerequisites && data.prerequisites.length > 0) {
                html += '<div class="details-section">';
                html += '<h5>Prerequisites:</h5>';
                html += '<ul>';
                data.prerequisites.forEach(prereq => {
                    html += `<li>${prereq.prerequisite_code} - ${prereq.prerequisite_name}</li>`;
                });
                html += '</ul>';
                html += '</div>';
            } else {
                html += '<p><strong>Prerequisites:</strong> None</p>';
            }
            
            if (data.corequisites && data.corequisites.length > 0) {
                html += '<div class="details-section">';
                html += '<h5>Corequisites:</h5>';
                html += '<ul>';
                data.corequisites.forEach(coreq => {
                    html += `<li>${coreq.coreq_code} - ${coreq.coreq_name}</li>`;
                });
                html += '</ul>';
                html += '</div>';
            } else {
                html += '<p><strong>Corequisites:</strong> None</p>';
            }
            
            if (data.sections && data.sections.length > 0) {
                html += '<div class="details-section">';
                html += '<h5>Available Sections:</h5>';
                html += '<ul>';
                data.sections.forEach(section => {
                    html += `<li>${section.day} - ${section.time_start} to ${section.time_end} (${section.room}) - Capacity: ${section.capacity}</li>`;
                });
                html += '</ul>';
                html += '</div>';
            }
            
            html += '</div>';
            content.innerHTML = html;
            showModal('subjectDetailsModal');
        } else {
            showAlert(data.message || 'Failed to load subject details.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while loading subject details.', 'error');
    });
}

