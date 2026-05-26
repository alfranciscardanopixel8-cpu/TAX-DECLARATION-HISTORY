$log = Get-Content "storage\logs\laravel.log" -Raw
$matches = [regex]::Matches($log, '\[\d{4}-\d{2}-\d{2}[^\]]+\] local\.ERROR:[^"]+')
if ($matches.Count -gt 0) {
    Write-Host "Latest error:"
    Write-Host $matches[$matches.Count - 1].Value.Substring(0, [Math]::Min(500, $matches[$matches.Count - 1].Value.Length))
}
