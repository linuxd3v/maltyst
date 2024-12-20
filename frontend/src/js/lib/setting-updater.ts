
// Method to fetch current settings from the server
async loadSettings() {
  try {
    const response = await fetch(`${this.saveEndpoint}?option=maltystMauticApiUrl`, {
      method: 'GET', // HTTP GET request to fetch data
      headers: {
        'Content-Type': 'application/json', // Specify JSON format
      },
    });

    // When status code is outside the range of 200–299
    if (!response.ok) {
      throw new Error('Failed to fetch settings');
    }

    const data = await response.json(); // Parse response JSON
    this.apiUrl = data.maltystMauticApiUrl || ''; // Set the API URL if available
  } catch (error) {
    console.error('Error loading settings:', error); // Log errors to the console
  }
}

// Method to save updated settings to the server
async saveSettings() {
  try {
    const response = await fetch(this.saveEndpoint, {
      method: 'POST', // HTTP POST request to save data
      headers: {
        'Content-Type': 'application/json', // Specify JSON format
        'X-WP-Nonce': window.maltystData.nonce, // Include a security nonce
      },
      body: JSON.stringify({
        option_name: 'maltystMauticApiUrl', // Name of the option being saved
        option_value: this.apiUrl, // Value of the option to save
      }),
    });

  // When status code is outside the range of 200–299
    if (!response.ok) {
      throw new Error('Failed to save settings');
    }

    alert('Settings saved successfully!'); // Notify user of success
  } catch (error) {
    console.error('Error saving settings:', error); // Log errors to the console
    alert('Failed to save settings. Please try again.'); // Notify user of failure
  }
}