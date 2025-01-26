export class MauticInstanceChecker {

  // Method to fetch settings from the server
  async getMauticStatus(mauticServer: Record<string, string>): Promise<Record<string, any> | null> {
    try {
      const response = await fetch(`${window.maltystData.MALTYST_ROUTE}/check-mautic-status`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': window.maltystData.nonce,
        },
        body: JSON.stringify(mauticServer), // Include areaName and settings in the body
      });
  
      if (!response.ok) {
        throw new Error('Failed to fetch the mautic status');
      }

      // Get and parse data
      const data = await response.json(); // Parse the response JSON
      return data;
    } catch (error) {
      console.error('loadSettings: Error retrieving mautic status:', error);
    }

    return null;
  }


}