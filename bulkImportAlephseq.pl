#!/bin/perl

# perl version of bulkImportAlephseq.php
# sample usage:
# perl bulkImportAlephseq.pl -type MARCMaker -instances holdings_examples/fc_bib_2_examples.mrk -holdings holdings_examples/fc_bib_hol_2_examples.mrk
#
use strict;
use warnings;

# use Data::Walk;
use Data::GUID;
use Data::Dump qw(dump);
use Encode qw(encode_utf8);
use HTTP::Request();
use JSON::MaybeXS qw(encode_json);
use JSON::MaybeXS qw(decode_json);
use Data::Format::Pretty::JSON qw(format_pretty);
use LWP::UserAgent;
use Getopt::Long qw(GetOptions);
use Pod::Usage qw(pod2usage);
# use LWP::ConsoleLogger::Easy qw(debug_ua);
use Catmandu;
use Storable qw(dclone);

my $ua = LWP::UserAgent->new;

my $req;
my $res;

# debug_ua($ua);
$ua->agent("MyApp/0.1");

my $okapiurl = 'http://172.31.1.52:9130';
my $tenant = 'diku';
my $password = 'admin';
my $username = 'diku_admin';
my $instancesfixfile = 'instancesfix.txt';
my $holdingsfixfile = 'holdingsfix.txt';
my $fixmijf = 'fixesMiJ.txt';
my $filestype = 'ALEPHSEQ';
my $instancesfile = '';
my $holdingsfile = '';
my $itemsfile = '';
my $man = 0;
my $help = 0;

pod2usage("$0: No files given.")  if ((@ARGV == 0) && (-t STDIN));

GetOptions(
  'okapiurl:s' => \$okapiurl,
  'tenant:s' => \$tenant,
  'password:s' => \$password,
  'user:s' => \$username,
  'instancefixes:s' => \$instancesfixfile,
  'holdingsfixes:s' => \$holdingsfixfile,
  'mijfix:s' => \$fixmijf,
  'type:s' => \$filestype,
  'instances:s' => \$instancesfile,
  'holdings:s' => \$holdingsfile,
  'items:s' => \$itemsfile,
  'help|?' => \$help,
  'man' => \$man
) or pod2usage(2);
pod2usage(1) if $help;
pod2usage(-verbose => 2) if $man;

$req = HTTP::Request->new(
 'POST',
 "$okapiurl/authn/login",
 [ 'X-Okapi-Tenant' => $tenant,
   'Content-Type' => 'application/json; charset=UTF-8',
   'Accept' => 'application/json' ],
 encode_utf8(encode_json({ 'username' => $username, 'password' => $password }))
);

$res = $ua->request($req);
die "error: ".$res->status_line."\n" unless $res->is_success;

my $token = $res->header('X-Okapi-Token');

$req = HTTP::Request->new(
  'GET',
  "$okapiurl/locations?limit=2000",
  ['X-Okapi-Tenant' => $tenant,
  'Content-Type' => 'application/json; charset=UTF-8',
  'Accept' => 'application/json',
  'X-Okapi-Token' => $token ]
);

$res = $ua->request($req);
die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;

my $locations = decode_json($res->content);

my $holdings = {};

my $instancesimporter = Catmandu->importer('MARC', 'type' => $filestype, 'file' => $instancesfile);
my $instancesfix = Catmandu->fixer($instancesfixfile);
my $mijfix = Catmandu->fixer($fixmijf);

my $instancesIDsysno = {};

$instancesimporter->each( sub {

  my $data = shift;

  my $mijd = dclone $data;

  $instancesfix->fix($data);

  # my $instanceID = $data->{'id'};
  my $sysno = $data->{'hrid'};
  $instancesIDsysno->{$sysno} = $data->{'id'};

  $req = HTTP::Request->new(
    'POST',
    "$okapiurl/instance-storage/instances",
    ['X-Okapi-Tenant' => $tenant,
    'Content-Type' => 'application/json; charset=UTF-8',
    'Accept' => 'text/plain',
    'X-Okapi-Token' => $token ],
    encode_json($data)
  );

  $res = $ua->request($req);
  die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;

  $mijfix->fix($mijd);

  $req = HTTP::Request->new(
    'PUT',
    "$okapiurl/instance-storage/instances/".$instancesIDsysno->{$sysno}."/source-record/marc-json",
    ['X-Okapi-Tenant' => $tenant,
    'Content-Type' => 'application/json; charset=UTF-8',
    'Accept' => 'text/plain',
    'X-Okapi-Token' => $token ],
    encode_utf8(encode_json($mijd))
  );

  $res = $ua->request($req);
  die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;

  foreach ( map {exists($_->{'Z30'}) ? $_->{'Z30'} : ()} @{$mijd->{'fields'}} ) {
    foreach ( @{$_->{'subfields'}} ){
      if( exists($_->{'1'}) && !exists($holdings->{$_->{'1'}.'-'.$instancesIDsysno->{$sysno}}) ){
        my $bibcode = $_->{'1'};
        $holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'id'} = Data::GUID->new->as_string;
        $holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'instanceId'} = $instancesIDsysno->{$sysno};
        $holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'holdingsTypeId'} = '0c422f92-0f4d-4d32-8cbe-390ebc33a3e5'; #Lembrar de fazer um if para serial se for issue       
        $holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'permanentLocationId'} = (map { $_->{'code'} eq $bibcode ? $_->{'id'} : () } @{$locations->{'locations'}})[0];

        $req = HTTP::Request->new(
          'PUT',
          "$okapiurl/holdings-storage/holdings/".$holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'id'},
          ['X-Okapi-Tenant' => $tenant,
          'Content-Type' => 'application/json; charset=UTF-8',
          'Accept' => 'text/plain',
          'X-Okapi-Token' => $token ],
          encode_utf8(encode_json($holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}))
        );
        $res = $ua->request($req);
        die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;
      }
    }

    my $item = {};
    
    $item->{'id'} = Data::GUID->new->as_string;
    $item->{'materialTypeId'} = '1a54b431-2e4f-452d-9cae-9cee66c9a892';
    $item->{'permanentLoanTypeId'} = '2b94c631-fca9-4892-a730-03ee529ffe27';
    $item->{'status'}->{'name'} = 'Available';
    foreach ( @{$_->{'subfields'}} ){
      if(exists($_->{'1'})){
        $item->{'holdingsRecordId'} = $holdings->{$_->{'1'}.'-'.$instancesIDsysno->{$sysno}}->{'id'};
      }
      if(exists($_->{'3'})){
        $item->{'itemLevelCallNumber'} = $_->{'3'};
      }
      if(exists($_->{'5'})){
        $item->{'barcode'} = $_->{'5'};
      }
    }

    $req = HTTP::Request->new(
      'PUT',
      "$okapiurl/item-storage/items/".$item->{'id'},
      ['X-Okapi-Tenant' => $tenant,
      'Content-Type' => 'application/json; charset=UTF-8',
      'Accept' => 'text/plain',
      'X-Okapi-Token' => $token ],
      encode_utf8(encode_json($item))
    );
    $res = $ua->request($req);
    die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;

    undef $item;    
  }

});

if(-e $holdingsfile){

  my $holdingsimporter = Catmandu->importer('MARC', 'type' => $filestype, 'file' => $holdingsfile);
  my $holdingsfix = Catmandu->fixer($holdingsfixfile);

  $holdingsimporter->each( sub {

    my $data = shift;
    $holdingsfix->fix($data);

    my $sysno = $data->{'sysno'};
    my $bibcode = $data->{'location'};

    $holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'id'} = Data::GUID->new->as_string;
    $holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'instanceId'} = $instancesIDsysno->{$sysno};
    $holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'holdingsTypeId'} = '0c422f92-0f4d-4d32-8cbe-390ebc33a3e5'; #Lembrar de fazer um if para serial se for issue       
    $holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'permanentLocationId'} = (map { $_->{'code'} eq $bibcode ? $_->{'id'} : () } @{$locations->{'locations'}})[0];
    $holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'callNumber'} = $data->{'callNumber'};

    $req = HTTP::Request->new(
      'PUT',
      "$okapiurl/holdings-storage/holdings/".$holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}->{'id'},
      ['X-Okapi-Tenant' => $tenant,
      'Content-Type' => 'application/json; charset=UTF-8',
      'Accept' => 'text/plain',
      'X-Okapi-Token' => $token ],
      encode_utf8(encode_json($holdings->{$bibcode.'-'.$instancesIDsysno->{$sysno}}))
    );
    $res = $ua->request($req);
    die "error: " . $res->status_line . ": " . $res->content . "\n" unless $res->is_success;

  });
}

__END__

=head1 NAME

Bulk Import to Folio

=head1 SYNOPSIS

  Options:
   -okapiurl       ( default http://172.31.1.52:9130 )
   -tenant         ( default diku )
   -password       ( default admin )
   -user           ( default diku_admin )
   -instancefixes  ( default instancesfix.txt )
   -holdingsfixes  ( default holdingsfix.txt )
   -mijfix         ( default fixesMiJ.txt )
   -type           ( default ALEPHSEQ )
   -instances      instances file
   -holdings       holdings file
   -items          items file ( not yet )
   -help           print help
   -man            call manpage

=head1 OPTIONS

=over 4

=item B<-help>

This bulk importer aims to aid on an importing task when bringing records, holdings and items from a catmandu compatible marc descripted set towards folio.

=item B<-man>

Prints the manual page and exits.

=back

=head1 DESCRIPTION

B<This program> will read the given input file(s) and do something
useful with the contents thereof.

=cut
