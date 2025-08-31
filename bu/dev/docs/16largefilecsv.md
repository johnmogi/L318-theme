# Feature Request: Large CSV Import with Chunk Processing

## Problem Statement
When importing very large CSV files (250+ rows) of student registration codes, the current implementation may:
- Time out during execution
- Consume excessive server memory
- Provide poor user feedback during processing

## Proposed Solution
Implement chunked processing with:

1. **File Splitting**
   - Read the CSV in chunks of 50-100 rows at a time
   - Process each chunk separately
   - Maintain progress tracking between chunks

2. **Memory Management**
   - Free memory between chunks
   - Use temporary storage if needed

3. **User Feedback**
   - Progress bar/percentage
   - Estimated time remaining
   - Error handling per chunk

4. **Technical Implementation**
```php
// Pseudo-code example
$chunk_size = 100;
$handle = fopen($csv_file, 'r');
$total = count(file($csv_file));

while (!feof($handle)) {
    $chunk = [];
    for ($i = 0; $i < $chunk_size && !feof($handle); $i++) {
        $chunk[] = fgetcsv($handle);
    }
    
    // Process chunk
    process_registration_codes($chunk);
    
    // Update progress
    update_progress($processed, $total);
}
```

## Benefits
- Handles large files without timeouts
- Better memory usage
- Improved user experience
- Easier error recovery
