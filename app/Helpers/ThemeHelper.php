<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;

class ThemeHelper
{
    protected array $htmlAttributes = [];
    protected array $htmlClasses = [];
    protected array $javascriptFiles = [];
    protected array $cssFiles = [];
    protected array $vendors = [];

    public function addHtmlAttribute(string $scope, string $name, string $value): void
    {
        $this->htmlAttributes[$scope][$name] = $value;
    }

    public function addHtmlClass(string $scope, string $class): void
    {
        if (!isset($this->htmlClasses[$scope])) {
            $this->htmlClasses[$scope] = [];
        }
        $this->htmlClasses[$scope][] = $class;
    }

    public function addJavascriptFile(string $file): void
    {
        $this->javascriptFiles[] = $file;
    }

    public function addCssFile(string $file): void
    {
        $this->cssFiles[] = $file;
    }

    public function addVendors(array $vendors): void
    {
        $this->vendors = array_merge($this->vendors, $vendors);
    }

    public function printHtmlAttributes(string $scope): string
    {
        if (empty($this->htmlAttributes[$scope])) {
            return '';
        }

        $attributes = [];
        foreach ($this->htmlAttributes[$scope] as $key => $value) {
            $attributes[] = sprintf('%s="%s"', $key, e($value));
        }

        return implode(' ', $attributes);
    }

    public function printHtmlClasses(string $scope): string
    {
        if (empty($this->htmlClasses[$scope])) {
            return '';
        }

        return sprintf('class="%s"', implode(' ', array_unique($this->htmlClasses[$scope])));
    }

    public function getSvgIcon(string $path, string $classNames = 'svg-icon'): string
    {
        $fullPath = public_path('assets/media/icons/' . $path);
        if (!File::exists($fullPath)) {
            return '';
        }
        $svgContent = File::get($fullPath);
        return preg_replace('/<svg /', '<svg class="' . e($classNames) . '" ', $svgContent, 1);
    }

    public function getJavascriptFiles(): array { return $this->javascriptFiles; }
    public function getCssFiles(): array { return $this->cssFiles; }
    public function getVendors(): array { return $this->vendors; }
}
