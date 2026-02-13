/**
 * Date formatting utilities for article timestamps
 */

/**
 * Formats a date string to a relative time format (e.g., "5m ago", "2h ago")
 * For dates older than 7 days, returns a formatted date string
 * 
 * @param dateString - ISO date string or any valid date string
 * @returns Formatted relative time or date string
 * 
 * @example
 * formatRelativeTime('2024-02-13T10:30:00Z') // "2h ago"
 * formatRelativeTime('2024-01-01T10:30:00Z') // "Jan 1, 2024"
 */
export const formatRelativeTime = (dateString: string): string => {
  if (!dateString) return 'Just now';
  
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  // Handle future dates (edge case)
  if (diffMs < 0) return 'Just now';
  
  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  
  // For older dates, show formatted date
  return date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined 
  });
};

/**
 * Formats a date string to a full date format
 * 
 * @param dateString - ISO date string or any valid date string
 * @returns Formatted date string (e.g., "Feb 13, 2024")
 * 
 * @example
 * formatDate('2024-02-13T10:30:00Z') // "Feb 13, 2024"
 */
export const formatDate = (dateString: string): string => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric' 
  });
};

/**
 * Formats a date string to a long format with day name
 * 
 * @param dateString - ISO date string or any valid date string
 * @returns Formatted date string (e.g., "Tuesday, February 13, 2024")
 * 
 * @example
 * formatLongDate('2024-02-13T10:30:00Z') // "Tuesday, February 13, 2024"
 */
export const formatLongDate = (dateString: string): string => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    weekday: 'long',
    month: 'long', 
    day: 'numeric', 
    year: 'numeric' 
  });
};

/**
 * Formats a date string to include time
 * 
 * @param dateString - ISO date string or any valid date string
 * @returns Formatted date and time string (e.g., "Feb 13, 2024 at 10:30 AM")
 * 
 * @example
 * formatDateTime('2024-02-13T10:30:00Z') // "Feb 13, 2024 at 10:30 AM"
 */
export const formatDateTime = (dateString: string): string => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const dateStr = date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric' 
  });
  const timeStr = date.toLocaleTimeString('en-US', { 
    hour: 'numeric', 
    minute: '2-digit',
    hour12: true 
  });
  return `${dateStr} at ${timeStr}`;
};

/**
 * Check if a date is within the last N days
 * 
 * @param dateString - ISO date string or any valid date string
 * @param days - Number of days to check against
 * @returns Boolean indicating if date is within the specified days
 * 
 * @example
 * isWithinDays('2024-02-13T10:30:00Z', 7) // true if within last 7 days
 */
export const isWithinDays = (dateString: string, days: number): boolean => {
  if (!dateString) return false;
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / 86400000);
  return diffDays <= days;
};