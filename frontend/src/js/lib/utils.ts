import { listenerCount } from "process";



// utils.ts
export function handleInput(component: any, e: Event) {
  const target = e.target as HTMLInputElement;
  const fieldName = target.id; // Assuming `id` matches the property name
  component[fieldName] = target.value;
}



// static list of all of the components (settings areas)
// const areas: Record<string, string[]> = {
//   'mautic-instance': [],
//   'preference-centers': [],
//   'optin-usecases': [],
//   'new-post-notifications': [],
//   'other-settings': []
// };

// Static list of all of the components (settings areas)
export const allSettingsAreas: string[] = [
  'mautic-instance',
  'preference-centers',
  'optin-usecases',
  'new-post-notifications',
  'other-settings',
];
