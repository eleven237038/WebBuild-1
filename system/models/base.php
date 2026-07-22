<?php
/**
 * base.php
 *
 * @copyright 2020 opencart.cn - All Rights Reserved
 * @link https://www.guangdawangluo.com
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2020-06-2020/6/29 14:39
 * @modified 2020-06-2020/6/29 14:39
 */

namespace Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Base extends Model
{
    public $timestamps = false;
    protected $modelName = '';

    /** @var \Illuminate\Database\Capsule\Manager|null */
    private static $capsule;
    private static $capsuleReady = false;

    /**
     * Lazily bootstrap the Eloquent capsule on first model use. Most requests
     * (guest browsing, admin catalog) never touch an Eloquent model, so paying
     * ~0.18s to load+boot Illuminate on every request is pure waste. The capsule
     * is created once, on the first Base instantiation or an explicit ensureCapsule()
     * call (e.g. the debug bar). The PDO connection itself stays lazy (opened on
     * first query), so even bootstrapping costs nothing for requests that load a
     * model but never query it.
     */
    public static function ensureCapsule()
    {
        if (self::$capsuleReady) {
            return self::$capsule;
        }
        self::$capsuleReady = true;
        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => \DB_HOSTNAME,
            'database'  => \DB_DATABASE,
            'username'  => \DB_USERNAME,
            'password'  => \DB_PASSWORD,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => \DB_PREFIX,
            'strict'    => false,
            'fetch'     => \PDO::FETCH_ASSOC,
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        self::$capsule = $capsule;
        return $capsule;
    }

    public static function getCapsule()
    {
        return self::$capsule;
    }

    public function __construct(array $attributes = [])
    {
        self::ensureCapsule();

        if (!$this->table) {
            $this->setTable($this->getCurrentClassName());
        }

        if ($this->primaryKey == 'id') {
            $this->setKeyName($this->getPrimaryName());
        }
        $this->modelName = str_replace('\\', '', Str::snake(static::class));
        parent::__construct($attributes);
    }

    public function getCurrentClassName()
    {
        return Str::snake(class_basename($this));
    }

    public function getPrimaryName()
    {
        return $this->getTable() . '_id';
    }

    public function getForeignKey()
    {
        return Str::snake(class_basename($this)) . '_id';
    }

    public function primaryValue()
    {
        return $this->{$this->getPrimaryName()};
    }

    public function getAllFields()
    {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($row) {
            $table = $row->getTable();
            if (\Schema::hasColumn($table, 'created_at')) {
                $row->created_at = Carbon::now()->toDateTimeString();
            }
            if (\Schema::hasColumn($table, 'date_added')) {
                $row->date_added = Carbon::now()->toDateTimeString();
            }

            if (\Schema::hasColumn($table, 'updated_at')) {
                $row->updated_at = Carbon::now()->toDateTimeString();
            }
            if (\Schema::hasColumn($table, 'date_modified')) {
                $row->date_modified = Carbon::now()->toDateTimeString();
            }
        });

        self::saving(function ($row) {
            $table = $row->getTable();
            if (\Schema::hasColumn($table, 'updated_at')) {
                $row->updated_at = Carbon::now()->toDateTimeString();
            }
            if (\Schema::hasColumn($table, 'date_modified')) {
                $row->date_modified = Carbon::now()->toDateTimeString();
            }
        });
    }
}