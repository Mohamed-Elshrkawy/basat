<?php
namespace App\Traits;
/**
 * Trait HasFallbackData
 *
 * This trait provides a mechanism for resolving model relationships
 * and falling back to a `_data` column when the relationship is unavailable.
 *
 * **Usage**:
 * Add this trait to any model that has a relationship and a `_data` column as a fallback.
 *
 * **Naming Conventions**:
 * - The column storing fallback data must be named as `relationName_data`.
 * - The dynamic attribute to access this logic must be named as `relationName_details`.
 *
 * **Example**:
 * For a model with a `scooter` relationship:
 * - Fallback column: `scooter_data`
 * - Dynamic key: `scooter_details`
 *
 * ```php
 * class Trip extends Model {
 *     use HasFallbackData;
 *     public function scooter() {
 *         return $this->belongsTo(Scooter::class);
 *     }
 * }
 *
 * $trip = Trip::find(1);
 * $details = $trip->scooter_details; // Resolves to the scooter relationship or scooter_data
 * ```
 *
 * @package App\Traits
 */
trait HasFallbackData
{
    /**
     * Dynamically resolve relation details with fallback data.
     *
     * This method checks if a relationship exists and returns it.
     * If the relationship does not exist, it falls back to data stored
     * in a `_data` column. The fallback data is expected to be stored
     * as JSON and is converted to an object for consistent access.
     *
     * @param string $relation    The name of the relationship to check.
     * @param string $dataColumn  The name of the fallback data column (e.g., `relation_data`).
     * @return object|null        Returns the relationship or an object containing fallback data, or null if neither exists.
     */
    public function getRelationWithFallback(string $relation, string $dataColumn)
    {
        if ($this->{$relation}) {
            return $this->{$relation};
        }
        if ($this->{$dataColumn}) {
            $data = is_array($this->{$dataColumn}) ? $this->{$dataColumn} : json_decode($this->{$dataColumn}, true);
            if ($data && is_array($data)) {
                return (object) $data; // Convert to object for consistent access
            }
        }
        return null;
    }
    /**
     * Dynamically resolve attributes like `getScooterDetailsAttribute`.
     *
     * This method automatically handles dynamic attributes ending with `_details`.
     * It resolves these attributes by checking for an existing relationship
     * or falling back to a `_data` column.
     *
     * @
     */
    public function __get($key)
    {
        if (str_ends_with($key, '_details')) {
            $relation   = str_replace('_details', '', $key);
            $dataColumn = "{$relation}_data";
            return $this->getRelationWithFallback($relation, $dataColumn);
        }
        return parent::__get($key);
    }
}
