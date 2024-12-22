export class SettingsManager {

  // Method to fetch settings from the server
  async loadSettings(optionNames: string[]): Promise<Record<string, any>> {
    try {
      // Encode the list of strings into a query string
      const query = new URLSearchParams(optionNames.map((name) => ['option_names[]', name])).toString();
      
      // Send the GET request with the encoded query
      const response = await fetch(`${window.maltystData.MALTYST_ROUTE}/get-settings?${query}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });
  
      if (!response.ok) {
        throw new Error('Failed to fetch settings');
      }
  
      const data = await response.json(); // Parse the response JSON
      return data.options; // Return the `options` object from the server response
    } catch (error) {
      console.error('Error loading settings:', error);
      throw error; // Rethrow the error for further handling if necessary
    }
  }

  // Method to save updated settings to the server
  async saveSettings(settings: Record<string, string>): Promise<boolean> {
    try {
      const response = await fetch(`${window.maltystData.MALTYST_ROUTE}/save-settings`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': window.maltystData.nonce,
        },
        body: JSON.stringify(settings), // Send the settings object directly
      });
  
      if (!response.ok) {
        throw new Error('Failed to save settings');
      }
  
      return true; // Indicate success
    } catch (error) {
      console.error('Error saving settings:', error);
      return false; // Indicate failure
    }
  } 
}