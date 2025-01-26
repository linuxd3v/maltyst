import {allSettingsAreas} from './utils';

export class SettingsManager {


  // loop over list and execute loadSettings(area) in parallel
  async loadAllSettings(): Promise<void> {
    try {
      // Execute loadSettings for each area in parallel using Promise.all
      await Promise.all(
        allSettingsAreas.map((settingsArea) => this.loadSettings(settingsArea))
      );
  
      console.log('loadAllSettings: All settings loaded successfully');
    } catch (error) {
      console.error('loadAllSettings: Error loading all settings:', error);

      // rethrow it  - because for further handling if necessary
      throw error;
    }
  }





  // Method to fetch settings from the server
  async loadSettings(area: string): Promise<Record<string, any> | null> {
    try {
      const response = await fetch(`${window.maltystData.MALTYST_ROUTE}/get-settings`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
      });
  
      if (!response.ok) {
        console.error('loadSettings:Failed to fetch settings:', response);
        return null;
      }
  
      const data: Record<string, any> = await response.json(); // Parse the response JSON as a dictionary
      return data;
    } catch (error) {
      console.error('loadSettings: Error loading settings:', error);
      // rethrow it  - because for further handling if necessary
      throw error;
    }
  }




  // Method to save updated settings to the server
  async saveSettings(areaName: string, settings: Record<string, any>): Promise<boolean> {
    try {
      const bodyData = {
        areaName,
        settings,
      };
  
      const response = await fetch(`${window.maltystData.MALTYST_ROUTE}/save-settings`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': window.maltystData.nonce,
        },
        body: JSON.stringify(bodyData), // Include areaName and settings in the body
      });
  
      if (!response.ok) {
        throw new Error('Failed to save settings');
      }
  
      return true; // Indicate success
    } catch (error) {
      console.error('Error saving settings:', error);
      return false; // Indicate failure
      // rethrow it  - because for further handling if necessary
      throw error;
    }
  }
}