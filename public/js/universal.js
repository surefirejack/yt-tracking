(function() {
    // Configuration
    const config = {
      trackingEndpoint: 'https://videostats.ai/api/track-lead',
      debug: true
    };
  
    // Get tenant UUID from embed snippet
    function getTenantUuid() {
      return window.ytTracking && window.ytTracking.tenantUuid ? window.ytTracking.tenantUuid : null;
    }
  
    // Function to extract relevant form data (name and email only)
    function getFormData(form) {
      const formData = new FormData(form);
      const data = {};
      
      if (config.debug) {
        console.log('Extracting data from form:', form);
        console.log('Form fields found:');
        for (let [key, value] of formData.entries()) {
          console.log(`  ${key}: ${value}`);
        }
      }
      
      // Get all input elements to check type and placeholder attributes
      const inputs = form.querySelectorAll('input, textarea, select');
      
      for (let [key, value] of formData.entries()) {
        const lowerKey = key.toLowerCase();
        
        // Find the corresponding input element
        const inputElement = Array.from(inputs).find(input => 
          input.name === key || input.id === key
        );
        
        // Look for name fields
        if (lowerKey.includes('name') || lowerKey === 'first_name' || lowerKey === 'last_name' || 
            lowerKey === 'firstname' || lowerKey === 'lastname' || lowerKey === 'full_name' ||
            lowerKey === 'fname' || lowerKey === 'lname') {
          if (config.debug) {
            console.log(`Found name field: ${key} = ${value}`);
          }
          if (data.name) {
            data.name += ' ' + value; // Combine first/last names
          } else {
            data.name = value;
          }
        }
        
        // Look for email fields - check field name, input type, and placeholder
        const isEmailByName = lowerKey.includes('email') || lowerKey === 'email_address' || 
                             lowerKey === 'emailaddress' || key.toLowerCase() === 'e-mail';
        const isEmailByType = inputElement && inputElement.type === 'email';
        const isEmailByPlaceholder = inputElement && inputElement.placeholder && 
                                   inputElement.placeholder.toLowerCase().includes('email');
        
        if (isEmailByName || isEmailByType || isEmailByPlaceholder) {
          if (config.debug) {
            console.log(`Found email field: ${key} = ${value}`, {
              byName: isEmailByName,
              byType: isEmailByType,
              byPlaceholder: isEmailByPlaceholder,
              inputType: inputElement ? inputElement.type : 'unknown',
              placeholder: inputElement ? inputElement.placeholder : 'none'
            });
          }
          data.email = value;
        }
      }
      
      if (config.debug) {
        console.log('Extracted form data:', data);
      }
      
      return data;
    }
  
    // Function to get dub_id cookie
    function getDubId() {
      const cookies = document.cookie.split(';');
      for (let cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'dub_id') {
          const dubId = decodeURIComponent(value);
          if (config.debug) {
            console.log('dub_id cookie found:', dubId);
          }
          return dubId;
        }
      }
      
      // If cookie not found, check URL parameters
      const urlParams = new URLSearchParams(window.location.search);
      const dubIdFromUrl = urlParams.get('dub_id');
      
      if (dubIdFromUrl) {
        // Set the cookie with the URL parameter value
        document.cookie = `dub_id=${encodeURIComponent(dubIdFromUrl)}; path=/; max-age=${30 * 24 * 60 * 60}`; // 30 days
        if (config.debug) {
          console.log('dub_id found in URL parameters and cookie set:', dubIdFromUrl);
        }
        return dubIdFromUrl;
      }
      
      return null;
    }
  
    // Function to send tracking data
    function trackLead(formData, source = 'form-submit') {
      const dubId = getDubId();
      const tenantUuid = getTenantUuid();
      
      if (config.debug) {
        console.log('trackLead called with:', { formData, source, dubId, tenantUuid });
      }
      
      if (!dubId) {
        if (config.debug) {
          console.warn('No dub_id cookie found - lead not tracked');
        }
        return;
      }

      if (!tenantUuid || tenantUuid === 'YOUR_TENANT_UUID') {
        if (config.debug) {
          console.warn('No valid tenant UUID found - lead not tracked. Make sure to replace YOUR_TENANT_UUID in the embed snippet.');
        }
        return;
      }

      const payload = {
        dub_id: dubId,
        tenant_uuid: tenantUuid,
        source: source
      };

      // Only include name and email if they exist
      if (formData.name) {
        payload.name = formData.name.trim();
      }
      if (formData.email) {
        payload.email = formData.email.trim();
      }

      if (config.debug) {
        console.log('Sending payload to webhook:', payload);
        console.log('Payload as JSON string:', JSON.stringify(payload));
      }

      // Send to your tracking endpoint
      fetch(config.trackingEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
      }).then(response => {
        if (config.debug) {
          console.log('Tracking response status:', response.status);
        }
      }).catch(error => {
        if (config.debug) {
          console.error('Failed to track lead:', error);
        }
      });
    }
  
    // Listen for form submissions
    function attachFormListeners() {
      document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.tagName === 'FORM') {
          const formData = getFormData(form);
          trackLead(formData, 'traditional-submit');
        }
      });
    }
  
    // Enhanced AJAX detection for page builders
    function interceptAjaxSubmissions() {
      // Override XMLHttpRequest
      const originalXHROpen = XMLHttpRequest.prototype.open;
      const originalXHRSend = XMLHttpRequest.prototype.send;
      
      XMLHttpRequest.prototype.open = function(method, url, ...args) {
        this._method = method;
        this._url = url;
        return originalXHROpen.apply(this, [method, url, ...args]);
      };
      
      XMLHttpRequest.prototype.send = function(data) {
        if (this._method === 'POST' && this._isFormSubmission(data)) {
          if (config.debug) {
            console.log('XHR POST detected, checking for form submission:', { 
              url: this._url, 
              data: data,
              dataType: typeof data,
              isFormData: data instanceof FormData 
            });
          }
          this.addEventListener('load', () => {
            if (this.status >= 200 && this.status < 300) {
              const formData = this._extractFormData(data);
              if (config.debug) {
                console.log('XHR form data extracted:', formData);
              }
              // Only track if we actually found name or email
              if (formData.name || formData.email) {
                trackLead(formData, 'xhr-submit');
              } else if (config.debug) {
                console.log('No name/email found in XHR data, skipping tracking');
              }
            }
          });
        }
        return originalXHRSend.apply(this, arguments);
      };
      
      XMLHttpRequest.prototype._isFormSubmission = function(data) {
        // Only consider it a form submission if:
        // 1. It's actual FormData, OR
        // 2. It's a URL-encoded string with form-like parameters
        if (data instanceof FormData) {
          return true;
        }
        
        if (typeof data === 'string' && data.includes('=')) {
          // Check if it looks like form data (has common form field patterns)
          const lowerData = data.toLowerCase();
          return lowerData.includes('name') || lowerData.includes('email') || 
                 lowerData.includes('firstname') || lowerData.includes('lastname') ||
                 lowerData.includes('email_address');
        }
        
        // Remove the overly broad object check
        return false;
      };
      
      XMLHttpRequest.prototype._extractFormData = function(data) {
        const extracted = {};
        
        if (data instanceof FormData) {
          for (let [key, value] of data.entries()) {
            const lowerKey = key.toLowerCase();
            
            // Look for name fields
            if (lowerKey.includes('name') || lowerKey === 'first_name' || lowerKey === 'last_name' || 
                lowerKey === 'firstname' || lowerKey === 'lastname' || lowerKey === 'full_name' ||
                lowerKey === 'fname' || lowerKey === 'lname') {
              if (extracted.name) {
                extracted.name += ' ' + value;
              } else {
                extracted.name = value;
              }
            }
            
            // Look for email fields
            if (lowerKey.includes('email') || lowerKey === 'email_address' || 
                lowerKey === 'emailaddress' || key.toLowerCase() === 'e-mail') {
              extracted.email = value;
            }
          }
        } else if (typeof data === 'string') {
          // Parse form-encoded data
          const params = new URLSearchParams(data);
          for (let [key, value] of params.entries()) {
            const lowerKey = key.toLowerCase();
            
            if (lowerKey.includes('name') || lowerKey === 'first_name' || lowerKey === 'last_name' || 
                lowerKey === 'firstname' || lowerKey === 'lastname' || lowerKey === 'full_name' ||
                lowerKey === 'fname' || lowerKey === 'lname') {
              if (extracted.name) {
                extracted.name += ' ' + value;
              } else {
                extracted.name = value;
              }
            }
            
            if (lowerKey.includes('email') || lowerKey === 'email_address' || 
                lowerKey === 'emailaddress' || key.toLowerCase() === 'e-mail') {
              extracted.email = value;
            }
          }
        }
        
        return extracted;
      };
  
      // Override fetch
      const originalFetch = window.fetch;
      window.fetch = function(url, options = {}) {
        // Skip tracking requests to avoid loops
        if (url === config.trackingEndpoint) {
          return originalFetch.apply(this, arguments);
        }
        
        if (options.method === 'POST' && options.body) {
          if (config.debug) {
            console.log('Fetch POST detected:', { 
              url: url, 
              bodyType: typeof options.body,
              isFormData: options.body instanceof FormData 
            });
          }
          const promise = originalFetch.apply(this, arguments);
          promise.then(response => {
            if (response.ok) {
              let formData = {};
              if (options.body instanceof FormData) {
                for (let [key, value] of options.body.entries()) {
                  const lowerKey = key.toLowerCase();
                  
                  // Look for name fields
                  if (lowerKey.includes('name') || lowerKey === 'first_name' || lowerKey === 'last_name' || 
                      lowerKey === 'firstname' || lowerKey === 'lastname' || lowerKey === 'full_name' ||
                      lowerKey === 'fname' || lowerKey === 'lname') {
                    if (formData.name) {
                      formData.name += ' ' + value;
                    } else {
                      formData.name = value;
                    }
                  }
                  
                  // Look for email fields
                  if (lowerKey.includes('email') || lowerKey === 'email_address' || 
                      lowerKey === 'emailaddress' || key.toLowerCase() === 'e-mail') {
                    formData.email = value;
                  }
                }
              } else if (typeof options.body === 'string') {
                try {
                  const parsed = JSON.parse(options.body);
                  Object.keys(parsed).forEach(key => {
                    const lowerKey = key.toLowerCase();
                    
                    if (lowerKey.includes('name') || lowerKey === 'first_name' || lowerKey === 'last_name' || 
                        lowerKey === 'firstname' || lowerKey === 'lastname' || lowerKey === 'full_name' ||
                        lowerKey === 'fname' || lowerKey === 'lname') {
                      if (formData.name) {
                        formData.name += ' ' + parsed[key];
                      } else {
                        formData.name = parsed[key];
                      }
                    }
                    
                    if (lowerKey.includes('email') || lowerKey === 'email_address' || 
                        lowerKey === 'emailaddress' || key.toLowerCase() === 'e-mail') {
                      formData.email = parsed[key];
                    }
                  });
                } catch (e) {
                  // If not JSON, try URL params
                  const params = new URLSearchParams(options.body);
                  for (let [key, value] of params.entries()) {
                    const lowerKey = key.toLowerCase();
                    
                    if (lowerKey.includes('name') || lowerKey === 'first_name' || lowerKey === 'last_name' || 
                        lowerKey === 'firstname' || lowerKey === 'lastname' || lowerKey === 'full_name' ||
                        lowerKey === 'fname' || lowerKey === 'lname') {
                      if (formData.name) {
                        formData.name += ' ' + value;
                      } else {
                        formData.name = value;
                      }
                    }
                    
                    if (lowerKey.includes('email') || lowerKey === 'email_address' || 
                        lowerKey === 'emailaddress' || key.toLowerCase() === 'e-mail') {
                      formData.email = value;
                    }
                  }
                }
              }
              
              if (config.debug) {
                console.log('Fetch form data extracted:', formData);
              }
              
              // Only track if we actually found name or email
              if (formData.name || formData.email) {
                trackLead(formData, 'fetch-submit');
              } else if (config.debug) {
                console.log('No name/email found in fetch data, skipping tracking');
              }
            }
          });
          return promise;
        }
        return originalFetch.apply(this, arguments);
      };
    }
  
    // Initialize when DOM is ready
    function init() {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
          attachFormListeners();
          interceptAjaxSubmissions();
        });
      } else {
        attachFormListeners();
        interceptAjaxSubmissions();
      }
    }
  
    init();
  })();