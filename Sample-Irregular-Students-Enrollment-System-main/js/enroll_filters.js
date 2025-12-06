/**
 * Search and Filter Functions for Enrollment Page
 */

// Filter subjects based on search and filters
function filterSubjects() {
    const searchTerm = document.getElementById('subjectSearch').value.toLowerCase();
    const yearFilter = document.getElementById('yearFilter').value;
    const unitsFilter = document.getElementById('unitsFilter').value;
    
    const subjectCards = document.querySelectorAll('.subject-card');
    let visibleCount = 0;
    
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
            document.querySelector('.available-subjects').appendChild(msg);
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
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

