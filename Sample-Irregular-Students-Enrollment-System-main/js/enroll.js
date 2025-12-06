/**
 * Enrollment Page JavaScript
 * Handles subject selection and validation
 */

// Add subject to selection
function addSubject(subjectCode, sectionId) {
    // Show loading state
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Validating...';
    
    // Make AJAX request to validate
    fetch('php/validate_add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `subject_code=${encodeURIComponent(subjectCode)}&section_id=${encodeURIComponent(sectionId)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid response from server: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        if (data.valid) {
            // Check for corequisites
            if (data.has_corequisites && data.corequisites && data.corequisites.length > 0) {
                // Show corequisite prompt
                const coreqNames = data.corequisites.map(c => c.subject_code + ' - ' + c.subject_name).join(', ');
                const message = `This subject has corequisite(s): ${coreqNames}\n\nWould you like to add the corequisite(s) together?`;
                
                if (confirm(message)) {
                    // Add main subject first
                    return addSubjectWithCorequisites(subjectCode, sectionId, data.corequisites, button, originalText);
                } else {
                    // User declined, just add the main subject
                    return addSubjectOnly(subjectCode, sectionId, button, originalText);
                }
            } else {
                // No corequisites, proceed normally
                return addSubjectOnly(subjectCode, sectionId, button, originalText);
            }
        } else {
            showAlert(data.message, 'error');
            button.disabled = false;
            button.textContent = originalText;
            throw new Error(data.message);
        }
    })
    .then(data => {
        // Handle response from addSubjectOnly or addSubjectWithCorequisites
        if (data && data.success) {
            // Reload page to show updated selection
            window.location.reload();
        } else if (data && !data.success) {
            showAlert(data.message || 'Failed to add subject.', 'error');
            button.disabled = false;
            button.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'An error occurred. Please try again. Check browser console for details.', 'error');
        button.disabled = false;
        button.textContent = originalText;
    });
}

// Add subject only (no corequisites)
function addSubjectOnly(subjectCode, sectionId, button, originalText) {
    return fetch('php/add_subject.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `subject_code=${encodeURIComponent(subjectCode)}&section_id=${encodeURIComponent(sectionId)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid response from server: ' + text.substring(0, 100));
            }
        });
    });
}

// Add subject with corequisites
function addSubjectWithCorequisites(subjectCode, sectionId, corequisites, button, originalText) {
    // First add the main subject
    return addSubjectOnly(subjectCode, sectionId, button, originalText)
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Failed to add main subject.');
        }
        
        // Then add each corequisite
        const coreqPromises = corequisites.map(coreq => {
            return fetch('php/add_subject.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `subject_code=${encodeURIComponent(coreq.subject_code)}&section_id=${encodeURIComponent(coreq.section_id)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid response from server: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                if (!data.success) {
                    console.warn(`Failed to add corequisite ${coreq.subject_code}:`, data.message);
                    // Don't throw - continue with other corequisites
                }
                return data;
            });
        });
        
        return Promise.all(coreqPromises)
        .then(() => {
            return { success: true, message: 'Subject and corequisites added successfully.' };
        });
    });
}

// Remove subject from selection
function removeSubject(subjectCode, sectionId) {
    if (!confirm('Remove this subject from your selection?')) {
        return;
    }
    
    fetch('php/remove_subject.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `subject_code=${encodeURIComponent(subjectCode)}&section_id=${encodeURIComponent(sectionId)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid response from server: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showAlert(data.message || 'Failed to remove subject.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'An error occurred. Please try again. Check browser console for details.', 'error');
    });
}

// Clear all selections
function clearSelection() {
    if (!confirm('Clear all selected subjects?')) {
        return;
    }
    
    fetch('php/clear_selection.php', {
        method: 'POST'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid response from server: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showAlert(data.message || 'Failed to clear selection.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message || 'An error occurred. Please try again. Check browser console for details.', 'error');
    });
}

// Finalize enrollment
function finalizeEnrollment() {
    // Get selected subjects from the page DOM
    const selectedList = document.querySelectorAll('.selected-subjects .subject-item');
    
    if (selectedList.length === 0) {
        showAlert('No subjects selected for enrollment.', 'error');
        return;
    }
    
    // Parse selected subjects from DOM
    const selectedSubjects = [];
    let totalUnits = 0;
    
    selectedList.forEach(item => {
        const info = item.querySelector('.subject-item-info');
        
        // Get subject code from strong tag
        const codeEl = info.querySelector('strong');
        const code = codeEl ? codeEl.textContent.trim() : '';
        
        // Get subject name - it's after the dash and before the first <br>
        const fullText = info.innerHTML;
        const nameMatch = fullText.match(/<\/strong>\s*-\s*(.+?)\s*<br>/);
        const name = nameMatch ? nameMatch[1].trim() : '';
        
        // Get section details from the first <small> tag
        const sectionSmall = info.querySelectorAll('small')[0];
        let day = '', timeStart = '', timeEnd = '', room = '';
        
        if (sectionSmall) {
            const sectionText = sectionSmall.textContent;
            // Format: "Section: Monday 10:00 AM - 12:00 PM (Room 304)"
            const sectionMatch = sectionText.match(/Section:\s*(\w+)\s+(.+?)\s*-\s*(.+?)\s*\((.+?)\)/);
            if (sectionMatch) {
                day = sectionMatch[1].trim();
                timeStart = sectionMatch[2].trim();
                timeEnd = sectionMatch[3].trim();
                room = sectionMatch[4].trim();
            }
        }
        
        // Get units from the second <small> tag
        const unitsSmall = info.querySelectorAll('small')[1];
        let units = 0;
        if (unitsSmall) {
            const unitsMatch = unitsSmall.textContent.match(/Units:\s*(\d+)/);
            units = unitsMatch ? parseInt(unitsMatch[1]) : 0;
        }
        totalUnits += units;
        
        selectedSubjects.push({
            subject_code: code,
            subject_name: name,
            section_day: day,
            section_time_start: timeStart,
            section_time_end: timeEnd,
            section_room: room,
            units: units
        });
    });
    
    // Build enrollment summary HTML
    // Get max units from a data attribute or use default
    const maxUnitsEl = document.querySelector('[data-max-units]');
    const maxUnits = maxUnitsEl ? parseInt(maxUnitsEl.getAttribute('data-max-units')) : 24;
    let summaryHTML = '<div class="enrollment-summary">';
    summaryHTML += '<div class="summary-header">';
    summaryHTML += '<h4>Enrollment Summary</h4>';
    summaryHTML += `<p><strong>Total Units:</strong> ${totalUnits} / ${maxUnits}</p>`;
    summaryHTML += `<p><strong>Number of Subjects:</strong> ${selectedSubjects.length}</p>`;
    summaryHTML += '</div>';
    summaryHTML += '<div class="summary-subjects">';
    summaryHTML += '<h5>Selected Subjects:</h5>';
    summaryHTML += '<ul class="summary-list">';
    
    selectedSubjects.forEach(subject => {
        summaryHTML += '<li>';
        summaryHTML += `<strong>${subject.subject_code}</strong> - ${subject.subject_name}`;
        summaryHTML += `<br><small>Section: ${subject.section_day} ${subject.section_time_start} - ${subject.section_time_end} (${subject.section_room})</small>`;
        summaryHTML += `<br><small>Units: ${subject.units}</small>`;
        summaryHTML += '</li>';
    });
    
    summaryHTML += '</ul>';
    summaryHTML += '</div>';
    summaryHTML += '</div>';
    
    // Update modal content
    document.getElementById('enrollmentSummary').innerHTML = summaryHTML;
    
    // Show modal
    showModal('confirmModal');
}

// Submit enrollment
function submitEnrollment() {
    const submitBtn = event.target;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    fetch('finalize.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message || 'Enrollment successful!', 'success');
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 2000);
        } else {
            showAlert(data.message || 'Enrollment failed.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Yes, Finalize Enrollment';
            closeModal('confirmModal');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Yes, Finalize Enrollment';
        closeModal('confirmModal');
    });
}

