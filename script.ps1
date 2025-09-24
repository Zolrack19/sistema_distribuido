$cpu = Get-CimInstance Win32_Processor |
  Select-Object Name, NumberOfCores, LoadPercentage

$ram = Get-CimInstance Win32_OperatingSystem |
  Select-Object
    @{Name='TotalGB';Expression={[math]::Round($_.TotalVisibleMemorySize/1MB,2)}},
    @{Name='FreeGB';Expression={[math]::Round($_.FreePhysicalMemory/1MB,2)}}

$disks = Get-CimInstance Win32_LogicalDisk -Filter 'DriveType=3' |
  Select-Object DeviceID,
    @{Name='TotalGB';Expression={[math]::Round($_.Size/1GB,2)}},
    @{Name='FreeGB';Expression={[math]::Round($_.FreeSpace/1GB,2)}}

[PSCustomObject]@{
  CPU   = $cpu
  RAM   = $ram
  Disks = $disks
} | ConvertTo-Json -Depth 3
